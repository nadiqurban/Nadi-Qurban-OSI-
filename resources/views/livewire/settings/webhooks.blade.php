<div>
    <x-ui.page-header title="Webhooks" subtitle="Terima notifikasi peristiwa sistem secara masa nyata ke endpoint anda." :breadcrumb="['Tetapan', 'Webhooks']">
        @if ($canManage)
            <x-slot:actions>
                <x-ui.button icon="plus" id="btn-add-hook" wire:click="openForm" mobile-block>Tambah Endpoint</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="mb-[22px] grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
        @foreach ($stats as $s)
            <x-ui.stat-card :icon="$s['icon']" :tone="$s['tone']" :value="$s['value']" :label="$s['label']" />
        @endforeach
    </div>

    <div class="grid grid-cols-1 items-start gap-[22px] lg:grid-cols-[1.5fr_1fr]">
        <section class="min-w-0 overflow-hidden rounded-[12px] border border-border bg-surface">
            <div class="border-b border-divider px-5 py-4 text-[14px] font-bold text-ink">Endpoint Berdaftar</div>
            @forelse ($this->endpoints as $e)
                @php
                    $rate = $e->total_30d > 0 ? $e->ok_30d / $e->total_30d * 100 : null;
                    $failing = $rate !== null && $rate < 90;
                @endphp
                <div wire:key="ep-{{ $e->id }}" class="border-b border-divider px-5 py-[14px] last:border-b-0">
                    <div class="flex items-center justify-between gap-3">
                        <span class="min-w-0 truncate font-mono text-[13px] font-semibold text-ink" title="{{ $e->url }}">{{ $e->url }}</span>
                        <x-ui.badge :tone="! $e->is_active ? 'neutral' : ($failing ? 'danger' : 'success')" class="shrink-0 !px-[10px] !text-[11px]">{{ ! $e->is_active ? 'Tidak Aktif' : ($failing ? 'Ralat' : 'Aktif') }}</x-ui.badge>
                    </div>
                    @if ($e->description)<div class="mt-1 text-[12px] text-muted">{{ $e->description }}</div>@endif
                    <div class="mt-[7px] flex flex-wrap items-center gap-x-[14px] gap-y-1 text-[11.5px] text-faint">
                        <span><i class="ph ph-lightning align-[-1px] text-[13px]"></i> {{ count($e->events) }} peristiwa</span>
                        <span><i class="ph ph-clock align-[-1px] text-[13px]"></i> {{ $e->last_at ? masa_lalu($e->last_at) : 'belum dihantar' }}</span>
                        @if ($rate !== null)<span @class(['text-success' => ! $failing, 'text-warning' => $failing])>{{ number_format($rate, 1) }}% berjaya</span>@endif
                    </div>
                    @if ($canManage)
                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                            <x-ui.button size="sm" variant="soft" icon="paper-plane-tilt" wire:click="ping({{ $e->id }})" class="!py-1.5 !text-[12px]">Uji</x-ui.button>
                            <x-ui.button size="sm" variant="secondary" icon="pencil-simple" wire:click="openForm({{ $e->id }})" class="!py-1.5 !text-[12px]">Edit</x-ui.button>
                            <x-ui.button size="sm" variant="secondary" :icon="$e->is_active ? 'pause' : 'play'" wire:click="toggleActive({{ $e->id }})" class="!py-1.5 !text-[12px]">{{ $e->is_active ? 'Jeda' : 'Aktifkan' }}</x-ui.button>
                            <x-ui.button size="sm" variant="danger-soft" icon="trash" wire:click="delete({{ $e->id }})" wire:confirm="Padam endpoint {{ $e->host() }}?" class="!py-1.5 !text-[12px]">Padam</x-ui.button>
                        </div>
                    @endif
                </div>
            @empty
                <x-ui.empty-state icon="webhooks-logo" title="Belum ada endpoint berdaftar." />
            @endforelse
        </section>

        <div class="flex min-w-0 flex-col gap-[22px]">
            @if ($secret)
                <section class="rounded-[12px] border border-border bg-surface p-5" x-data="{ copied: false, show: false }">
                    <div class="mb-3 flex items-center justify-between"><span class="text-[13px] font-bold text-ink">Signing Secret</span>
                        <button type="button" wire:click="rotateSecret" wire:confirm="Jana secret baharu? Penerima sedia ada perlu dikemas kini." class="text-[12px] font-semibold text-primary max-md:min-h-11 dark:text-[#c9ce93]">Jana semula</button></div>
                    <div class="flex gap-2">
                        <input x-ref="secret" :type="show ? 'text' : 'password'" value="{{ $secret }}" readonly aria-label="Signing secret" class="min-w-0 flex-1 rounded-[9px] border border-border bg-bg px-[11px] py-[9px] font-mono text-[12px] text-ink outline-none max-md:min-h-11">
                        <button type="button" @click="show = !show" class="shrink-0 rounded-[9px] border border-border px-3 text-[12px] font-semibold text-ink-2 max-md:min-h-11" x-text="show ? 'Sorok' : 'Papar'">Papar</button>
                        <x-ui.button size="sm" class="!px-[13px] !text-[12px]" x-on:click="navigator.clipboard.writeText($refs.secret.value); copied = true; setTimeout(() => copied = false, 1500)"><span x-text="copied ? 'Disalin' : 'Salin'">Salin</span></x-ui.button>
                    </div>
                    <p class="mt-2 text-[11.5px] leading-[1.5] text-faint">Gunakan secret ini untuk mengesahkan tandatangan <b>X-NQ-Signature</b> (HMAC-SHA256) setiap payload.</p>
                </section>
            @endif
            <section class="overflow-hidden rounded-[12px] border border-border bg-surface">
                <div class="border-b border-divider px-5 py-[14px] text-[13px] font-bold text-ink">Peristiwa Tersedia</div>
                @foreach (\App\Models\WebhookEndpoint::EVENTS as $key => $name)
                    @php $on = in_array($key, $enabled, true); @endphp
                    <div class="flex items-center justify-between gap-[10px] border-b border-divider px-5 py-[11px] last:border-b-0">
                        <div><div class="text-[12.5px] font-semibold text-ink">{{ $name }}</div><div class="font-mono text-[11px] text-faint">{{ $key }}</div></div>
                        <button type="button" role="switch" id="ev-{{ str_replace('.', '-', $key) }}" aria-checked="{{ $on ? 'true' : 'false' }}" aria-label="{{ $name }}" @if ($canManage) wire:click="toggleGlobalEvent('{{ $key }}')" @else disabled @endif class="flex items-center justify-center max-md:size-11">
                            <span @class(['relative h-5 w-9 rounded-[20px] transition-colors', 'bg-primary' => $on, 'bg-[#CBD5D0]' => ! $on])><span @class(['absolute top-0.5 size-4 rounded-full bg-white transition-all', 'right-0.5' => $on, 'left-0.5' => ! $on])></span></span>
                        </button>
                    </div>
                @endforeach
            </section>
        </div>
    </div>

    <section class="mt-[22px] min-w-0 overflow-hidden rounded-[12px] border border-border bg-surface">
        <div class="border-b border-divider px-5 py-4 text-[14px] font-bold text-ink">Log Penghantaran Terkini</div>
        <x-ui.data-table cols="1.4fr 1fr 0.8fr 1fr" class="!rounded-none !border-0">
            <x-slot:head>
                <span>Peristiwa</span><span>Endpoint</span><span class="text-center">Kod</span><span class="text-right">Masa</span>
            </x-slot:head>
            @forelse ($logs as $l)
                @php $tone = match ($l->status) { 'berjaya' => 'success', 'gagal' => 'danger', default => 'warning' }; @endphp
                <x-ui.tr wire:key="log-{{ $l->id }}" class="md:!py-3 md:!text-[12.5px]">
                    <x-ui.td span class="font-mono font-semibold text-primary dark:text-[#c9ce93]">{{ $l->event }}</x-ui.td>
                    <x-ui.td label="Endpoint" class="truncate font-mono text-muted" :title="$l->error">{{ $l->endpoint->host() }}</x-ui.td>
                    <x-ui.td label="Kod" align="center"><x-ui.badge :tone="$tone" variant="tag">{{ $l->http_status ?? ($l->status === 'menunggu' ? '…' : 'ERR') }}</x-ui.badge></x-ui.td>
                    <x-ui.td label="Masa" align="right" class="text-faint">{{ masa_lalu($l->updated_at) }}@if ($l->attempt > 1) · cubaan {{ $l->attempt }}@endif</x-ui.td>
                </x-ui.tr>
            @empty
                <x-slot:empty><x-ui.empty-state icon="paper-plane-tilt" title="Belum ada penghantaran." /></x-slot:empty>
            @endforelse
        </x-ui.data-table>
    </section>

    @if ($canManage)
        <x-ui.modal wire:model="showForm" :title="$editingId ? 'Edit Endpoint' : 'Tambah Endpoint'" subtitle="Daftar URL penerima webhook" icon="webhooks-logo" max-width="560px" :footer-border="false">
            <div class="flex flex-col gap-4">
                <x-ui.field label="Endpoint URL" id="hook-url" type="url" wire:model="url" placeholder="https://sistem-anda.com/webhook" />
                <x-ui.field label="Deskripsi" wire:model="description" placeholder="cth. Sistem ERP dalaman" />
                <div>
                    <span class="mb-1.5 block text-[12px] font-semibold text-ink-2">Peristiwa Dilanggan</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Models\WebhookEndpoint::EVENTS as $key => $name)
                            @php $sel = in_array($key, $events, true); @endphp
                            <button type="button" wire:click="toggleEvent('{{ $key }}')" aria-pressed="{{ $sel ? 'true' : 'false' }}" @class(['inline-flex items-center gap-1.5 rounded-[20px] px-3 py-1.5 text-[12px] font-semibold max-md:min-h-10', 'bg-primary text-white' => $sel, 'border border-border bg-bg text-muted' => ! $sel])>@if ($sel)<i class="ph-fill ph-check text-[12px]"></i>@endif{{ $name }}</button>
                        @endforeach
                    </div>
                    @error('events')<p class="mt-1 text-[11.5px] text-danger">{{ $message }}</p>@enderror
                </div>
            </div>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button icon="check" id="btn-save-hook" wire:click="save">Simpan Endpoint</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
