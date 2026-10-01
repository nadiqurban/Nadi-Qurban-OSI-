<?php

namespace App\Actions\Finance;

use App\Models\Quotation;
use App\Models\User;
use App\Support\Audit;
use App\Support\Sequence;
use App\Support\Settings;
use Illuminate\Validation\ValidationException;

/** Kewangan "Quotation Baharu": numbered QUO-{year}-{seq4}, items kept as a JSON snapshot. */
class CreateQuotation
{
    /**
     * @param  array{company: array<string, string>, name: string, phone?: string|null, email?: string|null, address?: string|null, note?: string|null, items: list<array{description: string, quantity: int, unit_price_sen: int}>}  $data
     */
    public function handle(array $data, User $actor): Quotation
    {
        $items = array_values(array_filter($data['items'], fn ($i) => trim($i['description']) !== '' && $i['quantity'] > 0));

        if ($items === []) {
            throw ValidationException::withMessages(['draft.items' => 'Tambah sekurang-kurangnya satu item.']);
        }

        $year = (int) app(Settings::class)->get('season.year', now()->year);
        $quotation = Quotation::query()->create([
            'quotation_no' => sprintf('QUO-%d-%04d', $year, Sequence::next("quotation-{$year}", 76)),
            'customer_name' => trim($data['name']),
            'customer_phone' => $data['phone'] ?? null,
            'customer_email' => $data['email'] ?? null,
            'customer_address' => $data['address'] ?? null,
            'company' => $data['company'],
            'items' => $items,
            'total_sen' => array_sum(array_map(fn ($i) => $i['quantity'] * $i['unit_price_sen'], $items)),
            'valid_until' => today()->addDays(30),
            'notes' => $data['note'] ?? null,
            'created_by' => $actor->id,
        ]);

        Audit::log('quotation.created', "Quotation {$quotation->quotation_no} dicipta untuk {$quotation->customer_name}", $quotation,
            properties: ['ref' => $quotation->quotation_no], causer: $actor, logName: 'finance');

        return $quotation;
    }
}
