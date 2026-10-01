<?php

namespace App\Livewire\Crm;

use App\Actions\Crm\Leads;
use App\Enums\LeadStage;
use App\Enums\Module;
use App\Models\Lead;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Lead detail (Sales CRM.dc.html isDetail): info + autosaved Catatan, Kemajuan Peringkat, Aktiviti & Nota, Tandakan Selesai, Tukar ke Tempahan. */
#[Layout('layouts::app')]
#[Title('Butiran Lead')]
class Show extends Component
{
    public Lead $lead;

    public string $leadNotes = '';

    public bool $noteOpen = false;

    public string $noteText = '';

    public function mount(Lead $lead): void
    {
        $this->lead = $lead->load(['owner', 'order', 'activities.user']);
        $this->leadNotes = (string) $lead->notes;
    }

    /** Catatan textarea autosave (wire:model.live.debounce). */
    public function updatedLeadNotes(Leads $leads): void
    {
        $this->authorize(Module::Crm->managePermission());
        $this->validate(['leadNotes' => ['nullable', 'string', 'max:5000']]);
        $leads->saveNotes($this->lead, $this->leadNotes);
    }

    public function toggleNote(): void
    {
        $this->noteOpen = ! $this->noteOpen;
        $this->noteText = '';
    }

    public function saveNote(Leads $leads): void
    {
        $this->authorize(Module::Crm->managePermission());
        $this->validate(['noteText' => ['nullable', 'string', 'max:2000']]);

        if (trim($this->noteText) !== '') {
            $leads->addNote($this->lead, $this->noteText, $this->actor());
            $this->lead->unsetRelation('activities');
        }

        $this->noteOpen = false;
        $this->noteText = '';
    }

    public function toggleDone(Leads $leads): void
    {
        $this->authorize(Module::Crm->managePermission());
        $leads->toggleDone($this->lead, $this->actor());
        $this->lead->refresh();
        $this->dispatch('toast', message: $this->lead->done_at ? 'Lead ditandakan selesai.' : 'Status selesai dibatalkan.', tone: $this->lead->done_at ? 'success' : 'info');
    }

    /** "Tukar ke Tempahan": open the new-order modal on /tempahan prefilled from this lead. */
    public function convert(): mixed
    {
        $this->authorize(Module::Crm->managePermission());
        $this->authorize(Module::Orders->managePermission());
        abort_unless($this->lead->stage === LeadStage::Closed && $this->lead->order_id === null, 422);

        return $this->redirectRoute('orders.index', ['lead' => $this->lead->id], navigate: true);
    }

    private function actor(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function render(): mixed
    {
        $lead = $this->lead->loadMissing(['owner', 'order', 'activities.user']);
        $step = $lead->stage->step();

        return view('livewire.crm.show', [
            'canManage' => auth()->user()?->can(Module::Crm->managePermission()) ?? false,
            'canConvert' => $lead->stage === LeadStage::Closed && $lead->order_id === null && (auth()->user()?->can(Module::Orders->managePermission()) ?? false),
            'info' => [
                'Lead ID' => $lead->lead_no,
                'Sumber' => $lead->source ?: '-',
                'Servis Diminati' => $lead->service.($lead->package ? ' · '.$lead->package : ''),
                'Anggaran Peserta' => $lead->participants ? $lead->participants.' orang' : '-',
                'Sales Owner' => $lead->owner->name ?? '-',
                'Tarikh Masuk' => tarikh($lead->created_at),
            ],
            'stages' => collect(LeadStage::cases())->map(fn (LeadStage $s) => [
                'label' => $s->label(),
                'state' => $s->step() < $step || ($lead->done_at && $s->step() === $step) ? 'done' : ($s->step() === $step ? 'current' : 'todo'),
            ])->all(),
        ]);
    }
}
