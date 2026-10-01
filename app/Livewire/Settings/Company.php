<?php

namespace App\Livewire\Settings;

use App\Enums\Module;
use App\Enums\Severity;
use App\Support\Audit;
use App\Support\DashboardStats;
use App\Support\Settings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Maklumat Syarikat — used on every receipt, PO, invoice, waybill and certificate.
 */
#[Layout('layouts::app')]
#[Title('Maklumat Syarikat')]
class Company extends Component
{
    /** @var array<string, string> */
    public array $company = [];

    public bool $saved = false;

    public string $seasonYear = '';

    public string $seasonTarget = '';

    public function mount(Settings $settings): void
    {
        $this->company = array_map(fn ($v) => (string) $v, $settings->group('company'));
        $this->seasonYear = (string) $settings->get('season.year', now()->year);
        $this->seasonTarget = (string) $settings->get('season.target_rm', '5000000');
    }

    public function save(Settings $settings): void
    {
        $this->authorize(Module::Settings->managePermission());

        $this->validate([
            'company.name' => ['required', 'string', 'max:150'],
            'company.ssm' => ['required', 'string', 'max:50'],
            'company.sst' => ['nullable', 'string', 'max:50'],
            'company.phone' => ['required', 'string', 'max:30'],
            'company.email' => ['required', 'email', 'max:150'],
            'company.address' => ['required', 'string', 'max:500'],
            'company.website' => ['nullable', 'string', 'max:150'],
            'company.bank_name' => ['required', 'string', 'max:100'],
            'company.bank_account' => ['required', 'string', 'max:50'],
            'company.bank_holder' => ['required', 'string', 'max:150'],
            'seasonYear' => ['required', 'integer', 'between:2020,2100'],
            'seasonTarget' => ['required', 'integer', 'min:0', 'max:9999999999'],
        ], attributes: [
            'seasonYear' => 'tahun musim', 'seasonTarget' => 'sasaran jualan',
            'company.name' => 'nama syarikat', 'company.ssm' => 'no. pendaftaran (SSM)', 'company.sst' => 'no. SST',
            'company.phone' => 'no. telefon', 'company.email' => 'emel rasmi', 'company.address' => 'alamat berdaftar',
            'company.website' => 'laman web', 'company.bank_name' => 'nama bank', 'company.bank_account' => 'no. akaun',
            'company.bank_holder' => 'nama pemegang akaun',
        ]);

        $before = $settings->group('company');
        $settings->setMany(collect($this->company)->mapWithKeys(fn ($v, $k) => ['company.'.$k => trim((string) $v)])->all()
            + ['season.year' => (string) (int) $this->seasonYear, 'season.target_rm' => (string) (int) $this->seasonTarget]);
        DashboardStats::flush();

        $changed = collect($this->company)->filter(fn ($v, $k) => (string) ($before[$k] ?? '') !== trim((string) $v))->keys()->all();

        if ($changed !== []) {
            Audit::log('settings.company', 'Maklumat syarikat dikemaskini', severity: Severity::Warning, properties: ['fields' => $changed], causer: auth()->user(), logName: 'settings');
        }

        $this->saved = true;
    }

    public function render(): mixed
    {
        return view('livewire.settings.company', [
            'canManage' => auth()->user()?->can(Module::Settings->managePermission()) ?? false,
        ]);
    }
}
