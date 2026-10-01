<?php

namespace App\Actions\Crm;

use App\Enums\LeadStage;
use App\Events\LeadStageChanged;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use App\Support\Audit;
use App\Support\Sequence;
use Illuminate\Support\Facades\DB;

/**
 * Sales CRM: create leads (LEAD-{seq}), move them across the Kanban (stage +
 * order persisted), notes/activities, Tandakan Selesai and Tukar ke Tempahan.
 */
class Leads
{
    /**
     * @param  array{name: string, company?: string|null, phone?: string|null, email?: string|null, service: string, package?: string|null, value_sen: int, notes?: string|null, source?: string|null}  $data
     */
    public function create(array $data, User $actor, LeadStage $stage = LeadStage::New): Lead
    {
        return DB::transaction(function () use ($data, $actor, $stage) {
            Lead::query()->where('stage', $stage)->increment('position');

            $lead = Lead::query()->create([
                'lead_no' => 'LEAD-'.Sequence::next('lead', 5040),
                'name' => trim($data['name']),
                'company' => ($data['company'] ?? null) ?: 'Individu',
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'service' => $data['service'],
                'package' => $data['package'] ?? null,
                'value_sen' => $data['value_sen'],
                'stage' => $stage,
                'position' => 0,
                'closed_at' => $stage === LeadStage::Closed ? now() : null,
                'source' => $data['source'] ?? 'Manual',
                'notes' => $data['notes'] ?? null,
                'owner_id' => $actor->id,
                'stage_changed_at' => now(),
            ]);

            $lead->activities()->create(['type' => 'created', 'title' => 'Lead dicipta', 'description' => 'Lead ditambah secara manual oleh '.$actor->name.'.', 'user_id' => $actor->id]);
            Audit::log('lead.created', "Lead {$lead->lead_no} ({$lead->name}) dicipta", $lead, properties: ['ref' => $lead->lead_no], causer: $actor, logName: 'crm');

            return $lead;
        });
    }

    /**
     * Drop a card into `$stage`; `$orderedIds` is the column's new card order.
     *
     * @param  list<int>  $orderedIds
     */
    public function move(Lead $lead, LeadStage $stage, array $orderedIds, User $actor): void
    {
        $from = $lead->stage;

        DB::transaction(function () use ($lead, $stage, $orderedIds, $from, $actor) {
            if ($from !== $stage) {
                $lead->forceFill([
                    'stage' => $stage,
                    'stage_changed_at' => now(),
                    'closed_at' => $stage === LeadStage::Closed ? now() : null,
                ])->save();

                $lead->activities()->create([
                    'type' => 'stage',
                    'title' => 'Peringkat ditukar',
                    'description' => $from->label().' → '.$stage->label(),
                    'user_id' => $actor->id,
                ]);
                Audit::log('lead.stage', "Lead {$lead->lead_no}: {$from->label()} → {$stage->label()}", $lead, properties: ['ref' => $lead->lead_no, 'from' => $from->value, 'to' => $stage->value], causer: $actor, logName: 'crm');
            }

            // Persist the column order (only ids already in this column or the moved card).
            $ids = Lead::query()->where('stage', $stage)->whereIn('id', $orderedIds)->pluck('id')->all();
            foreach (array_values(array_filter($orderedIds, fn ($id) => in_array($id, $ids, true))) as $pos => $id) {
                Lead::query()->whereKey($id)->update(['position' => $pos]);
            }
        });

        if ($from !== $stage) {
            event(new LeadStageChanged($lead, $from, $stage));
        }
    }

    /** Catatan textarea (autosave). */
    public function saveNotes(Lead $lead, ?string $notes): void
    {
        $lead->update(['notes' => $notes !== null && trim($notes) !== '' ? $notes : null]);
    }

    public function addNote(Lead $lead, string $text, User $actor): void
    {
        $lead->activities()->create(['type' => 'note', 'title' => 'Nota ditambah', 'description' => trim($text), 'user_id' => $actor->id]);
    }

    /** Tandakan Selesai (toggle). Marking done also closes the lead. */
    public function toggleDone(Lead $lead, User $actor): void
    {
        if ($lead->done_at) {
            $lead->update(['done_at' => null]);
            $lead->activities()->create(['type' => 'done', 'title' => 'Status selesai dibatalkan', 'user_id' => $actor->id]);

            return;
        }

        if ($lead->stage !== LeadStage::Closed) {
            $ids = [$lead->id, ...Lead::query()->where('stage', LeadStage::Closed)->orderBy('position')->pluck('id')->all()];
            $this->move($lead, LeadStage::Closed, $ids, $actor);
        }

        $lead->update(['done_at' => now()]);
        $lead->activities()->create(['type' => 'done', 'title' => 'Lead ditandakan selesai', 'user_id' => $actor->id]);
        Audit::log('lead.done', "Lead {$lead->lead_no} ditandakan selesai", $lead, properties: ['ref' => $lead->lead_no], causer: $actor, logName: 'crm');
    }

    /** Called after "Tukar ke Tempahan" creates the order. */
    public function linkOrder(Lead $lead, Order $order, User $actor): void
    {
        $lead->update(['order_id' => $order->id]);
        $lead->activities()->create(['type' => 'order', 'title' => 'Ditukar ke tempahan', 'description' => "Tempahan {$order->order_no} dicipta daripada lead ini.", 'user_id' => $actor->id]);
        Audit::log('lead.converted', "Lead {$lead->lead_no} ditukar ke tempahan {$order->order_no}", $lead, properties: ['ref' => $lead->lead_no, 'order_no' => $order->order_no], causer: $actor, logName: 'crm');
    }
}
