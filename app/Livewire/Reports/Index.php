<?php

namespace App\Livewire\Reports;

use App\Enums\Module;
use App\Enums\ReportType;
use App\Jobs\GenerateReport;
use App\Models\Country;
use App\Models\ReportExport;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Pusat Laporan (Pusat Laporan.dc.html): 6 quick templates, "Bina Laporan"
 * builder (type, date range, country chips, PDF/XLSX/CSV) → queued job →
 * "Laporan Dijana" list with Diproses/Siap + download.
 *
 * @property-read Collection<int, ReportExport> $recent
 */
#[Layout('layouts::app')]
#[Title('Pusat Laporan')]
class Index extends Component
{
    public string $type = 'jualan';

    public string $from = '';

    public string $to = '';

    /** @var list<int> */
    public array $countries = [];

    public string $format = 'pdf';

    public bool $showAll = false;

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    /** @return Collection<int, ReportExport> */
    #[Computed]
    public function recent(): Collection
    {
        return ReportExport::query()->with('requester')->latest()->latest('id')->limit($this->showAll ? 50 : 7)->get();
    }

    public function toggleCountry(int $id): void
    {
        $this->countries = in_array($id, $this->countries, true)
            ? array_values(array_diff($this->countries, [$id]))
            : [...$this->countries, $id];
    }

    /** Quick card: default format and the template's natural period. */
    public function quick(string $type): void
    {
        $t = ReportType::from($type);
        [$from, $to] = match ($t->frequency()) {
            'Mingguan' => [now()->subDays(6), now()],
            'Bulanan' => [now()->startOfMonth(), now()],
            default => [now()->startOfYear(), now()],
        };

        $this->queue($t, $t->defaultFormat(), $from, $to, []);
    }

    public function generate(): void
    {
        $this->validate([
            'type' => ['required', Rule::enum(ReportType::class)],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'format' => ['required', Rule::in(['pdf', 'xlsx', 'csv'])],
            'countries' => ['array'],
            'countries.*' => ['integer', 'exists:countries,id'],
        ], [], ['from' => 'Tarikh mula', 'to' => 'Tarikh akhir']);

        $this->queue(ReportType::from($this->type), $this->format, Carbon::parse($this->from), Carbon::parse($this->to), $this->countries);
    }

    /** @param list<int> $countries */
    private function queue(ReportType $type, string $format, Carbon $from, Carbon $to, array $countries): void
    {
        $this->authorize(Module::Reports->viewPermission());

        /** @var User $actor */
        $actor = auth()->user();
        $report = ReportExport::query()->create([
            'type' => $type,
            'name' => $type->label().' '.self::period($from, $to),
            'format' => $format,
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'countries' => $countries],
            'status' => ReportExport::PROCESSING,
            'requested_by' => $actor->id,
        ]);

        Audit::log('report.generated', "Laporan {$report->name} (".strtoupper($format).') dijana', $report, causer: $actor, logName: 'reports');
        GenerateReport::dispatch($report);

        unset($this->recent);
        $this->dispatch('toast', message: "{$report->name} sedang dijana.");
    }

    /** "Jun 2027" for a single month, otherwise "1 Jun – 30 Jun 2027". */
    public static function period(Carbon $from, Carbon $to): string
    {
        if ($from->isSameMonth($to) && $from->day === 1) {
            return (string) preg_replace('/^\d+ /', '', tarikh($from));
        }

        return preg_replace('/ \d{4}$/', '', tarikh($from)).' – '.tarikh($to);
    }

    public function render(): mixed
    {
        return view('livewire.reports.index', [
            'countryOptions' => Country::query()->orderBy('sort')->pluck('name', 'id')->all(),
            'processing' => $this->recent->contains(fn (ReportExport $r) => $r->status === ReportExport::PROCESSING),
        ]);
    }
}
