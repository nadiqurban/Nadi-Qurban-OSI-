<?php

namespace App\Livewire\Forms;

use App\Enums\VendorLevel;
use App\Enums\VendorStatus;
use App\Models\Vendor;
use Illuminate\Validation\Rule;
use Livewire\Form;

/** "Daftar Vendor Baharu" (660px) and "Edit Maklumat Vendor" (560px) modals. */
class VendorForm extends Form
{
    public ?int $vendorId = null;

    public string $code = '';

    public string $vendorNo = '';

    public string $name = '';

    public string $company = '';

    public string $supplier = '';

    public ?int $countryId = null;

    public string $level = 'silver';

    public string $status = 'aktif';

    public string $phone = '';

    public string $email = '';

    public string $picName = '';

    /** @var list<string> */
    public array $animals = [];

    public string $bankName = '';

    public string $bankHolder = '';

    public string $bankAccount = '';

    public string $swift = '';

    public string $bankAddress = '';

    public function forNew(): void
    {
        $this->reset();
        $this->resetValidation();

        // Suggest the next "SP 00x" after the highest existing code (still editable).
        $max = Vendor::withTrashed()->pluck('code')->map(fn (string $c) => (int) preg_replace('/\D+/', '', $c))->max() ?? 0;
        $this->code = sprintf('SP %03d', $max + 1);
    }

    public function fromVendor(Vendor $vendor): void
    {
        $this->resetValidation();
        $this->vendorId = $vendor->id;
        $this->code = $vendor->code;
        $this->vendorNo = (string) $vendor->vendor_no;
        $this->name = $vendor->name;
        $this->company = (string) $vendor->company;
        $this->supplier = (string) $vendor->supplier;
        $this->countryId = $vendor->country_id;
        $this->level = $vendor->level->value ?? 'silver';
        $this->status = $vendor->status->value;
        $this->phone = (string) $vendor->phone;
        $this->email = (string) $vendor->email;
        $this->picName = (string) $vendor->pic_name;
        $this->animals = $vendor->animals ?? [];
        $this->bankName = (string) $vendor->bank_name;
        $this->bankHolder = (string) $vendor->bank_holder;
        $this->bankAccount = (string) $vendor->bank_account;
        $this->swift = (string) $vendor->swift;
        $this->bankAddress = (string) $vendor->bank_address;
    }

    public function toggleAnimal(string $animal): void
    {
        if (! in_array($animal, Vendor::ANIMALS, true)) {
            return;
        }

        $this->animals = in_array($animal, $this->animals, true)
            ? array_values(array_diff($this->animals, [$animal]))
            : [...$this->animals, $animal];
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('vendors', 'code')->ignore($this->vendorId)],
            'vendorNo' => ['nullable', 'string', 'max:20', Rule::unique('vendors', 'vendor_no')->ignore($this->vendorId)],
            'name' => ['required', 'string', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'countryId' => ['required', 'integer', Rule::exists('countries', 'id')],
            'level' => ['required', Rule::enum(VendorLevel::class)],
            'status' => ['required', Rule::enum(VendorStatus::class)],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'picName' => ['nullable', 'string', 'max:120'],
            'animals' => ['array'],
            'animals.*' => [Rule::in(Vendor::ANIMALS)],
            'bankName' => ['nullable', 'string', 'max:100'],
            'bankHolder' => ['nullable', 'string', 'max:150'],
            'bankAccount' => ['nullable', 'string', 'max:60'],
            'swift' => ['nullable', 'string', 'max:20'],
            'bankAddress' => ['nullable', 'string', 'max:300'],
        ];
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return [
            'code' => 'kod vendor', 'vendorNo' => 'Vendor ID', 'name' => 'nama', 'company' => 'nama syarikat',
            'supplier' => 'nama supplier', 'countryId' => 'negara', 'level' => 'tahap vendor', 'phone' => 'no. telefon',
            'email' => 'emel', 'bankName' => 'nama bank', 'bankHolder' => 'pemegang akaun', 'bankAccount' => 'no. akaun',
            'swift' => 'kod swift', 'bankAddress' => 'alamat bank',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $blank = fn (string $v) => trim($v) === '' || trim($v) === '-' ? null : trim($v);

        return [
            'code' => mb_strtoupper(trim($this->code)),
            'vendor_no' => $blank($this->vendorNo),
            'name' => trim($this->name),
            'company' => $blank($this->company),
            'supplier' => $blank($this->supplier),
            'country_id' => $this->countryId,
            'level' => VendorLevel::from($this->level),
            'status' => VendorStatus::from($this->status),
            'phone' => $blank($this->phone),
            'email' => $blank($this->email),
            'pic_name' => $blank($this->picName) ?? $blank($this->supplier),
            'animals' => $this->animals,
            'bank_name' => $blank($this->bankName),
            'bank_holder' => $blank($this->bankHolder),
            'bank_account' => $blank($this->bankAccount),
            'swift' => $blank($this->swift),
            'bank_address' => $blank($this->bankAddress),
        ];
    }
}
