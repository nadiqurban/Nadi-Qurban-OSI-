<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/**
 * Cached key/value settings (company details, targets, gateway keys…).
 * Secrets are stored encrypted (`set($key, $value, encrypted: true)`).
 */
class Settings
{
    private const CACHE_KEY = 'settings.all';

    /** Company defaults (Tempahan & Pelanggan.dc.html → Maklumat Syarikat). */
    public const COMPANY_DEFAULTS = [
        'company.name' => 'Nadi Qurban Sdn. Bhd.',
        'company.ssm' => '1677511-A',
        'company.sst' => 'W10-2201-32000123',
        'company.phone' => '03-2181 8000',
        'company.email' => 'admin@nadiqurban.com',
        'company.address' => 'Aras 8, Menara Ilham, No. 15 Jalan Binjai, 50450 Kuala Lumpur, Malaysia',
        'company.website' => 'www.nadiqurban.com',
        'company.bank_name' => 'Maybank Berhad',
        'company.bank_account' => '5644 2311 0088',
        'company.bank_holder' => 'Nadi Qurban Sdn. Bhd.',
    ];

    /** @var array<string, string|null>|null */
    private ?array $loaded = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) && $all[$key] !== null ? $all[$key] : $default;
    }

    public function set(string $key, mixed $value, bool $encrypted = false): void
    {
        $stored = $value === null ? null : (string) $value;

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $encrypted && $stored !== null ? Crypt::encryptString($stored) : $stored, 'is_encrypted' => $encrypted],
        );

        $this->flush();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value, 'is_encrypted' => false]);
        }

        $this->flush();
    }

    /**
     * All settings under a prefix, e.g. group('company') → ['name' => …, 'ssm' => …].
     *
     * @return array<string, mixed>
     */
    public function group(string $prefix): array
    {
        $defaults = collect(self::COMPANY_DEFAULTS)->filter(fn ($v, $k) => str_starts_with($k, $prefix.'.'));

        return $defaults->keys()
            ->merge(collect($this->all())->keys()->filter(fn ($k) => str_starts_with($k, $prefix.'.')))
            ->unique()
            ->mapWithKeys(fn (string $k) => [substr($k, strlen($prefix) + 1) => $this->get($k, $defaults->get($k))])
            ->all();
    }

    public function flush(): void
    {
        $this->loaded = null;
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, string|null> */
    private function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        if (! Schema::hasTable('settings')) {
            return $this->loaded = [];
        }

        /** @var array<string, array{value: string|null, is_encrypted: bool}> $rows */
        $rows = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()
            ->get(['key', 'value', 'is_encrypted'])
            ->mapWithKeys(fn (Setting $s) => [$s->key => ['value' => $s->value, 'is_encrypted' => $s->is_encrypted]])
            ->all());

        return $this->loaded = collect($rows)
            ->map(fn (array $row) => $row['is_encrypted'] && $row['value'] !== null ? Crypt::decryptString($row['value']) : $row['value'])
            ->all();
    }
}
