<?php

namespace App\Livewire\Crm;

use App\Actions\Crm\Leads;
use App\Enums\LeadStage;
use App\Enums\Module;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Saluran Jualan (Sales CRM.dc.html list view): KPIs, Kanban with drag & drop
 * (SortableJS → moveLead) or Senarai table, and the Lead Baharu modal.
 *
 * @property-read Collection<int, Lead> $leads
 */
#[Layout('layouts::app')]
#[Title('Sales CRM')]
class Pipeline extends Component
{
    #[Url(as: 'paparan', except: 'kanban')]
    public string $mode = 'kanban';

    public bool $showNewLead = false;

    public string $newStage = 'baru';

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $company = '';

    public string $service = 'Qurban';

    public string $package = 'Delima';

    public string $value = '';

    public string $notes = '';

    /** @return Collection<int, Lead> */
    #[Computed]
    public function leads(): Collection
    {
        return Lead::query()->with('owner')->orderBy('position')->orderByDesc('id')->get();
    }

    /** @return list<array{icon: string, tone: string, value: string, label: string}> */
    public function kpis(): array
    {
        $all = $this->leads;
        $active = $all->filter(fn (Lead $l) => $l->stage !== LeadStage::Closed);
        $closed = $all->filter(fn (Lead $l) => $l->stage === LeadStage::Closed);
        $rate = $all->count() > 0 ? round($closed->count() / $all->count() * 100) : 0;

        return [
            ['icon' => 'funnel', 'tone' => 'primary', 'value' => (string) $active->count(), 'label' => 'Lead Aktif'],
            ['icon' => 'currency-circle-dollar', 'tone' => 'gold', 'value' => rm_short((int) $active->sum('value_sen')), 'label' => 'Nilai Saluran'],
            ['icon' => 'trophy', 'tone' => 'success', 'value' => $rate.'%', 'label' => 'Kadar Tukar'],
            ['icon' => 'handshake', 'tone' => 'info', 'value' => (string) $closed->filter(fn (Lead $l) => $l->closed_at?->isSameMonth(now()))->count(), 'label' => 'Ditutup (Bulan)'],
        ];
    }

    /** @param list<int> $orderedIds */
    public function moveLead(int $leadId, string $stage, array $orderedIds, Leads $leads): void
    {
        $this->authorize(Module::Crm->managePermission());

        $target = LeadStage::tryFrom($stage);
        abort_if($target === null, 422);

        $lead = Lead::query()->findOrFail($leadId);
        $leads->move($lead, $target, array_map('intval', $orderedIds), $this->actor());
        unset($this->leads);
    }

    public function openNewLead(string $stage = 'baru'): void
    {
        $this->authorize(Module::Crm->managePermission());
        $this->resetErrorBag();
        $this->reset('name', 'phone', 'email', 'company', 'service', 'package', 'value', 'notes');
        $this->newStage = (LeadStage::tryFrom($stage) ?? LeadStage::New)->value;
        $this->showNewLead = true;
    }

    public function saveLead(Leads $leads): void
    {
        $this->authorize(Module::Crm->managePermission());
        $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'service' => ['required', Rule::in(Lead::SERVICES)],
            'package' => ['nullable', Rule::in(Lead::PACKAGES)],
            'value' => ['nullable', 'regex:/^[0-9,]*\.?[0-9]{0,2}$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], ['name' => 'Nama Prospek', 'value' => 'Nilai', 'email' => 'Emel']);

        $lead = $leads->create([
            'name' => $this->name,
            'company' => trim($this->company) ?: null,
            'phone' => trim($this->phone) ?: null,
            'email' => trim($this->email) ?: null,
            'service' => $this->service,
            'package' => $this->package ?: null,
            'value_sen' => (int) round((float) str_replace(',', '', $this->value ?: '0') * 100),
            'notes' => trim($this->notes) ?: null,
        ], $this->actor(), LeadStage::tryFrom($this->newStage) ?? LeadStage::New);

        $this->showNewLead = false;
        unset($this->leads);
        $this->dispatch('toast', message: "Lead {$lead->name} ditambah ({$lead->lead_no}).");
    }

    private function actor(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function render(): mixed
    {
        $grouped = $this->leads->groupBy(fn (Lead $l) => $l->stage->value);

        return view('livewire.crm.pipeline', [
            'canManage' => auth()->user()?->can(Module::Crm->managePermission()) ?? false,
            'columns' => collect(LeadStage::cases())->map(fn (LeadStage $s) => [
                'stage' => $s,
                'leads' => $grouped->get($s->value, collect()),
            ])->all(),
        ]);
    }
}
