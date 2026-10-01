<?php

namespace App\Livewire\Audit;

use App\Enums\Severity;
use App\Models\User;
use App\Support\AuditAction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

/**
 * Log Audit Sistem (Audit Log.dc.html): stats, severity tabs, search, range,
 * table with detail side panel, "Aktiviti Mengikut Jenis" bars, CSV export.
 * Read-only — the audit trail cannot be edited or deleted from the app.
 *
 * @property-read LengthAwarePaginator<int, Activity> $logs
 */
#[Layout('layouts::app')]
#[Title('Audit Log')]
class Index extends Component
{
    use WithPagination;

    public const RANGES = ['24j' => '24 jam lepas', '7h' => '7 hari lepas', '30h' => '30 hari lepas', '90h' => '90 hari lepas', '12b' => '12 bulan lepas'];

    #[Url(as: 'tempoh', except: '7h')]
    public string $range = '7h';

    #[Url(as: 'tahap', except: '')]
    public string $severity = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public ?int $selectedId = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['range', 'severity', 'search'], true)) {
            $this->resetPage();
        }
    }

    public static function since(string $range): Carbon
    {
        return match ($range) {
            '24j' => now()->subDay(),
            '30h' => now()->subDays(30),
            '90h' => now()->subDays(90),
            '12b' => now()->subYear(),
            default => now()->subDays(7),
        };
    }

    /**
     * Shared by the table and the CSV export.
     *
     * @return Builder<Activity>
     */
    public static function filtered(string $range, string $severity, string $search): Builder
    {
        $term = trim($search);

        return Activity::query()
            ->where('created_at', '>=', self::since($range))
            ->when(Severity::tryFrom($severity), fn (Builder $q, Severity $s) => $q->where('severity', $s->value))
            ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('description', 'like', "%{$term}%")
                ->orWhere('event', 'like', "%{$term}%")
                ->orWhereHasMorph('causer', [User::class], fn (Builder $u) => $u->where('name', 'like', "%{$term}%"))));
    }

    /** @return LengthAwarePaginator<int, Activity> */
    #[Computed]
    public function logs(): LengthAwarePaginator
    {
        return self::filtered($this->range, $this->severity, $this->search)
            ->with('causer')->latest()->latest('id')->paginate(10);
    }

    #[Computed]
    public function selected(): ?Activity
    {
        return $this->selectedId ? Activity::query()->with(['causer', 'subject'])->find($this->selectedId) : null;
    }

    public function select(int $id): void
    {
        $this->selectedId = $this->selectedId === $id ? null : $id;
        unset($this->selected);
    }

    /** @return array{stats: list<array{icon: string, tone: string, value: string, label: string}>, types: list<array{icon: string, tone: string, label: string, count: int, width: int}>} */
    public function overview(): array
    {
        $base = Activity::query()->where('created_at', '>=', self::since($this->range));
        $total = (clone $base)->count();
        $warnings = (clone $base)->whereIn('severity', [Severity::Warning->value, Severity::Critical->value])->count();

        $byType = (clone $base)->selectRaw('event, COUNT(*) as n')->groupBy('event')->pluck('n', 'event')
            ->reduce(function (array $carry, $n, $event) {
                $t = AuditAction::type((string) $event);
                $carry[$t] = ($carry[$t] ?? 0) + (int) $n;

                return $carry;
            }, []);

        $changes = ($byType['create'] ?? 0) + ($byType['edit'] ?? 0) + ($byType['delete'] ?? 0);
        $rows = [
            ['icon' => 'pencil-simple', 'tone' => 'gold', 'label' => 'Perubahan Data', 'count' => $changes],
            ['icon' => 'sign-in', 'tone' => 'info', 'label' => 'Log Masuk / Keluar', 'count' => $byType['login'] ?? 0],
            ['icon' => 'shield-check', 'tone' => 'primary', 'label' => 'Verifikasi', 'count' => $byType['verify'] ?? 0],
            ['icon' => 'download-simple', 'tone' => 'purple', 'label' => 'Eksport', 'count' => $byType['export'] ?? 0],
            ['icon' => 'warning', 'tone' => 'danger', 'label' => 'Amaran Keselamatan', 'count' => $warnings],
        ];
        $max = max(1, ...array_column($rows, 'count'));

        return [
            'stats' => [
                ['icon' => 'list-checks', 'tone' => 'primary', 'value' => number_format($total), 'label' => 'Jumlah Log ('.str_replace(' lepas', '', self::RANGES[$this->range] ?? '7 hari').')'],
                ['icon' => 'sign-in', 'tone' => 'info', 'value' => number_format($byType['login'] ?? 0), 'label' => 'Log Masuk'],
                ['icon' => 'pencil-simple', 'tone' => 'gold', 'value' => number_format($changes), 'label' => 'Perubahan Data'],
                ['icon' => 'warning', 'tone' => 'danger', 'value' => number_format($warnings), 'label' => 'Amaran Keselamatan'],
            ],
            'types' => array_map(fn ($r) => $r + ['width' => (int) max(2, round($r['count'] / $max * 100))], $rows),
        ];
    }

    public function render(): mixed
    {
        return view('livewire.audit.index', ['overview' => $this->overview()]);
    }
}
