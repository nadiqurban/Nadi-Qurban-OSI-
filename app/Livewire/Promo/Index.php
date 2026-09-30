<?php

namespace App\Livewire\Promo;

use App\Actions\Catalog\DeleteCatalogItem;
use App\Actions\Catalog\SavePromoCode;
use App\Enums\DiscountType;
use App\Enums\Module;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Kod Promosi (Kod Promosi.dc.html): Aktif / Tamat Tempoh tabs, table, create/edit modal.
 *
 * @property-read Collection<int, PromoCode> $codes
 * @property-read list<array{icon: string, tone: string, value: string, label: string}> $stats
 * @property-read array{active: int, ended: int} $counts
 */
#[Layout('layouts::app')]
#[Title('Kod Promosi')]
class Index extends Component
{
    #[Url(as: 'tab', except: 'aktif')]
    public string $tab = 'aktif';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $description = '';

    public string $type = 'peratus';

    public string $value = '';

    public string $usageLimit = '';

    public string $expiresAt = '';

    public bool $isActive = true;

    /** @return Collection<int, PromoCode> */
    #[Computed]
    public function codes(): Collection
    {
        return PromoCode::query()
            ->when($this->tab === 'tamat', fn ($q) => $q->ended(), fn ($q) => $q->usable())
            ->orderByRaw('expires_at IS NULL, expires_at')
            ->orderBy('code')
            ->get();
    }

    /** @return array{active: int, ended: int} */
    #[Computed]
    public function counts(): array
    {
        return [
            'active' => PromoCode::query()->usable()->count(),
            'ended' => PromoCode::query()->ended()->count(),
        ];
    }

    /** @return list<array{icon: string, tone: string, value: string, label: string}> */
    #[Computed]
    public function stats(): array
    {
        return [
            ['icon' => 'ticket', 'tone' => 'primary', 'value' => number_format(PromoCode::query()->count()), 'label' => 'Jumlah Kod'],
            ['icon' => 'check-circle', 'tone' => 'success', 'value' => number_format($this->counts['active']), 'label' => 'Kod Aktif'],
            ['icon' => 'tag', 'tone' => 'warning', 'value' => number_format((int) PromoCode::query()->sum('used_count')), 'label' => 'Kali Digunakan'],
            ['icon' => 'hand-coins', 'tone' => 'info', 'value' => rm_short((int) PromoCode::query()->sum('total_discount_sen')), 'label' => 'Jumlah Diskaun'],
        ];
    }

    public function canManage(): bool
    {
        return auth()->user()?->can(Module::Promo->managePermission()) ?? false;
    }

    public function create(): void
    {
        $this->authorize(Module::Promo->managePermission());

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize(Module::Promo->managePermission());

        $p = PromoCode::query()->findOrFail($id);
        $this->resetForm();
        $this->editingId = $p->id;
        $this->code = $p->code;
        $this->description = (string) $p->description;
        $this->type = $p->type->value;
        $this->value = $p->type === DiscountType::Fixed
            ? number_format($p->value / 100, $p->value % 100 ? 2 : 0, '.', '')
            : (string) $p->value;
        $this->usageLimit = (string) ($p->usage_limit ?? '');
        $this->expiresAt = (string) $p->expires_at?->toDateString();
        $this->isActive = $p->is_active;
        $this->showForm = true;
    }

    public function autoCode(): void
    {
        $this->code = SavePromoCode::generateCode();
    }

    public function updatedCode(): void
    {
        $this->code = SavePromoCode::normalise($this->code);
    }

    public function save(SavePromoCode $save): void
    {
        $this->authorize(Module::Promo->managePermission());

        $this->code = SavePromoCode::normalise($this->code);

        $this->validate([
            'code' => ['required', 'string', 'min:3', 'max:30', Rule::unique('promo_codes', 'code')->ignore($this->editingId)],
            'description' => ['nullable', 'string', 'max:200'],
            'type' => ['required', Rule::enum(DiscountType::class)],
            'value' => $this->type === DiscountType::Percent->value
                ? ['required', 'integer', 'min:1', 'max:100']
                : ['required', function (string $attr, mixed $v, \Closure $fail) {
                    $sen = parse_rm(is_scalar($v) ? (string) $v : null);
                    if ($sen === null || $sen <= 0) {
                        $fail('Nilai mesti jumlah RM yang sah (cth. 50).');
                    }
                }],
            'usageLimit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'expiresAt' => ['nullable', 'date'],
        ], attributes: [
            'code' => 'kod', 'description' => 'keterangan', 'type' => 'jenis diskaun', 'value' => 'nilai',
            'usageLimit' => 'had guna', 'expiresAt' => 'tarikh sah hingga',
        ]);

        /** @var User $actor */
        $actor = auth()->user();

        $save->handle(
            $this->editingId ? PromoCode::query()->findOrFail($this->editingId) : null,
            [
                'code' => $this->code,
                'description' => trim($this->description) ?: null,
                'type' => $this->type,
                'value' => $this->type === DiscountType::Percent->value ? (int) $this->value : (int) parse_rm($this->value),
                'usage_limit' => $this->usageLimit === '' ? null : (int) $this->usageLimit,
                'expires_at' => $this->expiresAt ?: null,
                'is_active' => $this->isActive,
            ],
            $actor,
        );

        $this->showForm = false;
        $this->resetForm();
        unset($this->codes, $this->counts, $this->stats);
    }

    public function delete(int $id, DeleteCatalogItem $delete): void
    {
        $this->authorize(Module::Promo->managePermission());

        /** @var User $actor */
        $actor = auth()->user();
        $delete->handle(PromoCode::query()->findOrFail($id), $actor);

        unset($this->codes, $this->counts, $this->stats);
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'code', 'description', 'value', 'usageLimit', 'expiresAt');
        $this->type = DiscountType::Percent->value;
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render(): mixed
    {
        return view('livewire.promo.index');
    }
}
