<?php

namespace App\Livewire\Forms;

use App\Support\Settings;
use Illuminate\Support\Carbon;
use Livewire\Form;

/**
 * Shared draft for Kewangan "Cipta Invois" and "Quotation Baharu" modals:
 * editable company block (prefilled from Tetapan → Syarikat), customer, item rows.
 */
class FinanceDocForm extends Form
{
    public string $type = 'invoice';   // invoice | quote

    /** @var array{name: string, ssm: string, address: string, phone: string, email: string} */
    public array $company = ['name' => '', 'ssm' => '', 'address' => '', 'phone' => '', 'email' => ''];

    public string $name = '';

    public string $phone = '';

    public string $order = '';

    public string $email = '';

    public string $address = '';

    public string $due = '';

    public string $note = '';

    /** @var list<array{description: string, qty: string, price: string}> */
    public array $items = [];

    public function start(string $type): void
    {
        $this->resetErrorBag();
        $co = app(Settings::class)->group('company');

        $this->type = $type === 'quote' ? 'quote' : 'invoice';
        $this->company = [
            'name' => (string) ($co['name'] ?? ''),
            'ssm' => (string) ($co['ssm'] ?? ''),
            'address' => (string) ($co['address'] ?? ''),
            'phone' => (string) ($co['phone'] ?? ''),
            'email' => (string) ($co['email'] ?? ''),
        ];
        $this->name = $this->phone = $this->order = $this->email = $this->address = '';
        $this->due = today()->addDays(14)->toDateString();
        $this->note = $this->type === 'invoice'
            ? 'Sila jelaskan bayaran sebelum tarikh tempoh. Terima kasih.'
            : 'Sebut harga ini sah untuk tempoh yang dinyatakan. Harga tertakluk pada perubahan selepas tarikh luput.';
        $this->items = [['description' => 'Qurban Lembu — Delima', 'qty' => '1', 'price' => '3500']];
    }

    public function addItem(): void
    {
        $this->items[] = ['description' => '', 'qty' => '1', 'price' => ''];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public static function priceSen(string $price): int
    {
        return (int) round(((float) preg_replace('/[^0-9.]/', '', $price)) * 100);
    }

    public static function qty(string $qty): int
    {
        return max(0, (int) $qty);
    }

    public function totalSen(): int
    {
        return array_sum(array_map(fn ($i) => self::qty($i['qty']) * self::priceSen($i['price']), $this->items));
    }

    /** @return list<array{description: string, quantity: int, unit_price_sen: int}> */
    public function cleanItems(): array
    {
        return array_map(fn ($i) => [
            'description' => trim($i['description']),
            'quantity' => self::qty($i['qty']),
            'unit_price_sen' => self::priceSen($i['price']),
        ], $this->items);
    }

    /** @return array<string, mixed> */
    public function validationRules(): array
    {
        return [
            'draft.company.name' => ['required', 'string', 'max:150'],
            'draft.company.ssm' => ['nullable', 'string', 'max:40'],
            'draft.company.address' => ['nullable', 'string', 'max:300'],
            'draft.company.phone' => ['nullable', 'string', 'max:40'],
            'draft.company.email' => ['nullable', 'email', 'max:150'],
            'draft.name' => ['required', 'string', 'max:150'],
            'draft.phone' => ['nullable', 'string', 'max:30'],
            'draft.email' => ['nullable', 'email', 'max:150'],
            'draft.address' => ['nullable', 'string', 'max:300'],
            'draft.order' => ['nullable', 'string', 'max:30'],
            'draft.due' => $this->type === 'invoice' ? ['required', 'date'] : ['nullable'],
            'draft.note' => ['nullable', 'string', 'max:1000'],
            'draft.items' => ['required', 'array', 'min:1', 'max:30'],
            'draft.items.*.description' => ['required', 'string', 'max:200'],
            'draft.items.*.qty' => ['required', 'integer', 'min:1', 'max:9999'],
            'draft.items.*.price' => ['required', 'regex:/^[0-9,]*\.?[0-9]{0,2}$/'],
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'company' => $this->company,
            'name' => $this->name,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'address' => $this->address ?: null,
            'order' => $this->order ?: null,
            'due' => $this->due,
            'note' => $this->note ?: null,
            'items' => $this->cleanItems(),
        ];
    }

    /** View model for the A4 preview / PDF (same partial as a saved document). */
    public function document(string $number): object
    {
        return (object) [
            'type' => $this->type,
            'number' => $number,
            'company' => $this->company,
            'name' => $this->name ?: '-',
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'order' => $this->order,
            'date' => today(),
            'due' => $this->due ? Carbon::parse($this->due) : null,
            'note' => $this->note,
            'items' => array_map(fn ($i) => ['description' => $i['description'] ?: '-', 'quantity' => $i['quantity'], 'unit_price_sen' => $i['unit_price_sen']], $this->cleanItems()),
            'total_sen' => $this->totalSen(),
        ];
    }
}
