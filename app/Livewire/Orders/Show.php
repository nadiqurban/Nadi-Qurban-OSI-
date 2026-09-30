<?php

namespace App\Livewire\Orders;

use App\Actions\Orders\UpdateOrderDetails;
use App\Enums\MalaysianState;
use App\Enums\Module;
use App\Models\Order;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/** Butiran Tempahan (Tempahan & Pelanggan.dc.html detail view). */
#[Layout('layouts::app')]
class Show extends Component
{
    use WithFileUploads;

    public Order $order;

    #[Url(as: 'edit', except: false)]
    public bool $showEdit = false;

    public bool $showProof = false;

    /** @var list<string> */
    public array $participants = [];

    /** @var TemporaryUploadedFile|null */
    public $proof = null;

    /** @var array<string, string> */
    public array $edit = [];

    public function mount(Order $order): void
    {
        $this->order = $order;
        $this->loadParticipants();

        if ($this->showEdit && $this->canManage()) {
            $this->openEdit();
        } else {
            $this->showEdit = false;
        }
    }

    public function canManage(): bool
    {
        return auth()->user()?->can(Module::Orders->managePermission()) ?? false;
    }

    private function loadParticipants(): void
    {
        $this->order->load(['customer', 'country', 'participants', 'stageHistories', 'payment.media', 'payment.order']);
        $this->participants = $this->order->participantNames()->all();
    }

    // ------------------------------------------------------------ participants

    public function addParticipant(): void
    {
        $this->authorize(Module::Orders->managePermission());
        $this->participants[] = '';
    }

    public function removeParticipant(int $index): void
    {
        $this->authorize(Module::Orders->managePermission());
        unset($this->participants[$index]);
        $this->participants = array_values($this->participants);
    }

    public function saveParticipants(UpdateOrderDetails $update): void
    {
        $this->authorize(Module::Orders->managePermission());

        $this->validate(['participants.*' => ['nullable', 'string', 'max:150']], attributes: ['participants.*' => 'nama peserta']);

        $update->participants($this->order, $this->participants, $this->actor());
        $this->loadParticipants();
        $this->dispatch('toast', message: 'Senarai peserta disimpan.');
    }

    // ------------------------------------------------------------ payment proof

    public function updatedProof(UpdateOrderDetails $update): void
    {
        $this->authorize(Module::Orders->managePermission());

        $this->validate(['proof' => ['required', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:10240']], [
            'proof.mimes' => 'Bukti bayaran mesti PNG, JPG atau PDF.',
            'proof.max' => 'Bukti bayaran tidak boleh melebihi 10MB.',
        ]);

        $update->proof($this->order, $this->proof, $this->actor());
        $this->proof = null;
        $this->loadParticipants();
        $this->dispatch('toast', message: 'Bukti bayaran dimuat naik.');
    }

    // ------------------------------------------------------------ edit (Kemaskini)

    public function openEdit(): void
    {
        $this->authorize(Module::Orders->managePermission());

        $c = $this->order->customer;
        $this->edit = [
            'name' => $c->name, 'phone' => $c->phone, 'email' => (string) $c->email,
            'address' => (string) $c->address, 'postcode' => (string) $c->postcode, 'city' => (string) $c->city,
            'state' => (string) ($c->state ?: MalaysianState::Selangor->value),
            'year' => (string) $this->order->year,
            'implementation_date' => (string) $this->order->implementation_date?->toDateString(),
            'notes' => (string) $this->order->notes,
        ];
        $this->resetValidation();
        $this->showEdit = true;
    }

    public function saveEdit(UpdateOrderDetails $update): void
    {
        $this->authorize(Module::Orders->managePermission());

        $this->validate([
            'edit.name' => ['required', 'string', 'max:150'],
            'edit.phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'edit.email' => ['nullable', 'email', 'max:150'],
            'edit.address' => ['nullable', 'string', 'max:300'],
            'edit.postcode' => ['nullable', 'digits:5'],
            'edit.city' => ['nullable', 'string', 'max:80'],
            'edit.state' => ['required', Rule::enum(MalaysianState::class)],
            'edit.year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'edit.implementation_date' => ['nullable', 'date'],
            'edit.notes' => ['nullable', 'string', 'max:500'],
        ], ['edit.phone.regex' => 'No. telefon tidak sah (cth. 012-3456789).'], [
            'edit.name' => 'nama', 'edit.phone' => 'no. telefon', 'edit.email' => 'emel', 'edit.address' => 'alamat',
            'edit.postcode' => 'poskod', 'edit.city' => 'bandar', 'edit.state' => 'negeri', 'edit.year' => 'tahun',
            'edit.implementation_date' => 'tarikh pelaksanaan', 'edit.notes' => 'catatan',
        ]);

        $nullable = fn (string $k) => trim($this->edit[$k] ?? '') ?: null;

        $update->details($this->order, [
            'name' => trim($this->edit['name']),
            'phone' => trim($this->edit['phone']),
            'email' => $nullable('email'),
            'address' => $nullable('address'),
            'postcode' => $nullable('postcode'),
            'city' => $nullable('city'),
            'state' => $this->edit['state'],
        ], [
            'year' => (int) $this->edit['year'],
            'implementation_date' => $nullable('implementation_date'),
            'notes' => $nullable('notes'),
        ], $this->actor());

        $this->showEdit = false;
        $this->loadParticipants();
        $this->dispatch('toast', message: 'Tempahan dikemaskini.');
    }

    private function actor(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function render(): mixed
    {
        return view('livewire.orders.show', [
            'canManage' => $this->canManage(),
        ])->title($this->order->order_no.' · Tempahan');
    }
}
