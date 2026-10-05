@php
    $canManage = $this->canManage();
    $allRoles = $this->allRoles;
@endphp

<div>
    <x-ui.page-header title="Pengguna & Peranan" :breadcrumb="['Sistem', 'Pengguna & Peranan']">
        @if ($canManage)
            <x-slot:actions>
                <x-ui.button icon="user-plus" mobile-block wire:click="create">Tambah Pengguna</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    {{-- Top tabs (underline style, Pengguna & Peranan.dc.html mainTabs) --}}
    <div class="nq-scroll-x mb-[22px] flex items-center gap-2 border-b border-border" role="tablist">
        @foreach ([['pengguna', 'Pengguna', 'users'], ['peranan', 'Peranan & Kebenaran', 'shield-check']] as [$key, $label, $icon])
            <button type="button" role="tab" wire:click="$set('tab', '{{ $key }}')" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                    @class([
                        '-mb-px flex shrink-0 items-center gap-2 border-b-2 px-4 py-3 text-[13.5px] font-semibold',
                        'border-primary text-primary dark:border-[#c9ce93] dark:text-[#c9ce93]' => $tab === $key,
                        'border-transparent text-muted' => $tab !== $key,
                    ])>
                <i class="ph ph-{{ $icon }} text-[17px]"></i> {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($createdNotice)
        <div class="mb-5 flex items-start gap-[10px] rounded-[10px] border border-[#BBE5C9] bg-success-soft px-[14px] py-3" role="status">
            <i class="ph-fill ph-check-circle text-[18px] text-success"></i>
            <span class="flex-1 text-[13px] leading-[1.5] text-[#15803D]">{{ $createdNotice }}</span>
            <button type="button" wire:click="$set('createdNotice', null)" class="flex size-8 items-center justify-center text-success" aria-label="Tutup"><i class="ph ph-x text-[15px]"></i></button>
        </div>
    @endif

    {{-- ===================== USERS TAB ===================== --}}
    @if ($tab === 'pengguna')
        <div class="mb-5 grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
            @foreach ($this->stats as $s)
                <x-ui.stat-card :icon="$s['icon']" :tone="$s['tone']" :value="$s['value']" :label="$s['label']" />
            @endforeach
        </div>

        <x-ui.data-table cols="1.8fr 1.3fr 1fr 1fr 0.9fr" min-width="880px">
            <x-slot:head>
                <span>Pengguna</span><span>Peranan</span><span>Akses Terakhir</span><span class="text-center">Status</span><span class="text-right">Tindakan</span>
            </x-slot:head>

            @forelse ($this->users as $u)
                <x-ui.tr wire:key="user-{{ $u->id }}">
                    <x-ui.td span class="flex items-center gap-3">
                        <x-ui.avatar :name="$u->name" :tone="\App\Support\Tone::avatarFor($u->id - 1)" :size="38" />
                        <span class="min-w-0">
                            <span class="block truncate text-[13px] font-semibold text-ink">{{ $u->name }}</span>
                            <span class="block truncate text-[11.5px] text-faint">{{ $u->email }}</span>
                        </span>
                        <span class="ml-auto md:hidden"><x-ui.status-badge :status="$u->status" /></span>
                    </x-ui.td>
                    <x-ui.td label="Peranan" class="flex flex-wrap gap-1">
                        @foreach ($u->roles->sortBy('sort') as $r)
                            <x-ui.badge variant="tag" :tone="$r->tagTone()" class="px-[9px] text-[11px]">{{ $r->name }}</x-ui.badge>
                        @endforeach
                    </x-ui.td>
                    <x-ui.td label="Akses Terakhir" class="text-[12.5px] text-muted">{{ masa_lalu($u->last_seen_at) }}</x-ui.td>
                    <x-ui.td align="center" mobile="hide"><x-ui.status-badge :status="$u->status" /></x-ui.td>
                    <x-ui.td align="right" span class="flex gap-1.5 md:justify-end">
                        @if ($canManage)
                            <x-ui.button variant="secondary" size="sm" icon-only icon="pencil-simple" class="text-primary" wire:click="edit({{ $u->id }})" aria-label="Edit {{ $u->name }}" />
                            <x-ui.button variant="secondary" size="sm" icon-only icon="lock-key" class="text-danger" wire:click="openAccess({{ $u->id }})" aria-label="Akses & keselamatan {{ $u->name }}" />
                        @endif
                    </x-ui.td>
                </x-ui.tr>
            @empty
                <x-slot:empty>
                    <x-ui.empty-state icon="users" title="Tiada pengguna ditemui">Cuba ubah carian atau penapis peranan.</x-ui.empty-state>
                </x-slot:empty>
            @endforelse

            @if ($this->users->total() > 0)
                <x-slot:footer>
                    <x-ui.pagination :paginator="$this->users" noun="pengguna" />
                </x-slot:footer>
            @endif

            <x-slot:toolbar>
                <x-ui.search-input placeholder="Cari nama atau emel" wire:model.live.debounce.300ms="search" />
                <x-ui.filter-select label="Peranan" icon="funnel" wire:model.live="roleFilter" :options="$allRoles->pluck('name', 'name')->all()" />
            </x-slot:toolbar>
        </x-ui.data-table>
    @endif

    {{-- ===================== ROLES TAB ===================== --}}
    @if ($tab === 'peranan')
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-[repeat(auto-fill,minmax(230px,1fr))]">
            @foreach ($allRoles as $r)
                <a href="{{ route('users.role', $r) }}" wire:navigate wire:key="role-{{ $r->id }}"
                   class="block rounded-[12px] border border-border bg-surface px-5 py-[18px] transition hover:border-primary/40 hover:shadow-pop">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="flex size-10 items-center justify-center rounded-[10px] {{ \App\Support\Tone::classes($r->tone ?? 'neutral') }}"><i class="ph ph-{{ $r->icon ?? 'shield' }} text-[21px]"></i></span>
                        <span class="rounded-[20px] bg-bg px-[9px] py-0.5 text-[12px] font-bold text-muted">{{ $r->users_count }}</span>
                    </div>
                    <div class="text-[14.5px] font-bold text-ink">{{ $r->name }}</div>
                    <div class="mt-1 text-[12px] leading-[1.5] text-muted">{{ $r->description }}</div>
                </a>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-[12px] border border-border bg-surface">
            <div class="flex flex-wrap items-center justify-between gap-[10px] px-4 pt-5 pb-4 md:px-6">
                <div>
                    <h2 class="text-[15px] font-bold text-ink">Matriks Kebenaran</h2>
                    <p class="mt-[3px] text-[12.5px] text-muted">Kebenaran akses mengikut modul &amp; peranan</p>
                </div>
                <div class="flex flex-wrap items-center gap-[14px] text-[12px] text-muted">
                    @foreach (\App\Enums\AccessLevel::cases() as $level)
                        <span class="flex items-center gap-[5px]"><i class="{{ $level->icon() }} {{ $level->iconClass() }} text-[16px]"></i>{{ $level->shortLabel() }}</span>
                    @endforeach
                    @if ($canManage)
                        <x-ui.button size="sm" icon="floppy-disk" wire:click="saveMatrix" class="md:ml-1.5">Simpan Matriks</x-ui.button>
                    @endif
                    @if ($matrixSaved)
                        <span class="inline-flex items-center gap-[5px] font-semibold text-success"><i class="ph-fill ph-check-circle text-[15px]"></i> Disimpan</span>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <div style="--cols: 1.6fr repeat({{ $allRoles->count() }}, 1fr); --n: {{ $allRoles->count() }}" class="w-max md:w-auto md:min-w-[880px] [&>div]:max-md:grid-cols-[124px_repeat(var(--n),76px)]">
                    <div class="sticky-first grid grid-cols-(--cols) border-y border-border bg-head px-4 py-[11px] text-[11px] font-bold tracking-[.3px] text-faint uppercase md:px-6 [&>*:first-child]:sticky [&>*:first-child]:left-0 [&>*:first-child]:bg-head">
                        <span>Modul</span>
                        @foreach ($allRoles as $r)
                            <span class="text-center">{{ $r->name }}</span>
                        @endforeach
                    </div>
                    @foreach ($modules as $module)
                        <div class="grid grid-cols-(--cols) items-center border-b border-divider px-4 py-[13px] md:px-6 [&>*:first-child]:sticky [&>*:first-child]:left-0 [&>*:first-child]:bg-surface" wire:key="m-{{ $module->value }}">
                            <span class="pr-2 text-[13px] font-semibold text-ink-2">{{ $module->label() }}</span>
                            @foreach ($allRoles as $r)
                                @php $level = \App\Enums\AccessLevel::from($matrix[$r->id][$module->value] ?? 'N'); @endphp
                                <span class="flex justify-center">
                                    @if ($canManage && ! $r->isLockedMatrix())
                                        <button type="button" wire:click="cycle({{ $r->id }}, '{{ $module->value }}')"
                                                class="flex size-11 items-center justify-center rounded-[6px] hover:bg-bg md:-my-[2.5px] md:size-6"
                                                aria-label="{{ $r->name }} — {{ $module->label() }}: {{ $level->shortLabel() }} (klik untuk tukar)">
                                            <i class="{{ $level->icon() }} {{ $level->iconClass() }} text-[19px]"></i>
                                        </button>
                                    @else
                                        <span class="flex size-11 items-center justify-center md:-my-[2.5px] md:size-6" title="{{ $r->isLockedMatrix() ? 'Super Admin sentiasa akses penuh' : $level->shortLabel() }}">
                                            <i class="{{ $level->icon() }} {{ $level->iconClass() }} text-[19px]"></i>
                                        </span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================== MODAL: Tambah / Edit Pengguna ===================== --}}
    <x-ui.modal wire:model="showForm" :title="$editingId ? 'Edit Pengguna' : 'Tambah Pengguna'"
                :subtitle="$editingId ? $name : 'Cipta akaun pengguna baharu'" :icon="$editingId ? 'user-gear' : 'user-plus'"
                :max-width="$editingId ? '440px' : '480px'" :footer-border="false">
        <form id="user-form" wire:submit="save" class="grid grid-cols-1 gap-4 md:grid-cols-2" novalidate>
            <x-ui.field label="Nama Penuh" wire:model="name" placeholder="Nama pengguna" span autocomplete="off" />
            <x-ui.field label="Emel" type="email" wire:model="email" placeholder="emel@nadiqurban.com" :span="(bool) $editingId" autocomplete="off" />
            @unless ($editingId)
                <x-ui.field label="No. Telefon" wire:model="phone" placeholder="01X-XXXXXXX" inputmode="tel" />
            @endunless

            <div class="col-span-full">
                <span class="mb-2 block text-[12px] font-semibold text-ink-2">Peranan <span class="font-normal text-faint">(boleh pilih lebih daripada satu)</span></span>
                <div class="flex flex-wrap gap-2" role="group" aria-label="Peranan">
                    @foreach ($allRoles as $r)
                        @php $on = in_array($r->name, $roles, true); @endphp
                        <button type="button" wire:click="toggleRole('{{ $r->name }}')" aria-pressed="{{ $on ? 'true' : 'false' }}"
                                @class([
                                    'inline-flex min-h-9 items-center gap-1.5 rounded-[20px] px-[13px] py-[7px] text-[12.5px] font-semibold',
                                    'bg-primary text-white' => $on,
                                    'border border-border bg-bg text-ink-3' => ! $on,
                                ])>
                            @if ($on)<i class="ph-fill ph-check text-[13px]"></i>@endif{{ $r->name }}
                        </button>
                    @endforeach
                </div>
                @error('roles')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
            </div>

            @if ($editingId)
                <x-ui.field label="Status" as="select" wire:model="status" span>
                    @foreach (\App\Enums\UserStatus::cases() as $st)
                        <option value="{{ $st->value }}">{{ $st->label() }}</option>
                    @endforeach
                </x-ui.field>
                @if ($this->canSetPassword())
                    <div class="col-span-full">
                        <x-ui.field id="new-password" label="Kata Laluan Baharu" hint="(pilihan)" wire:model="newPassword" placeholder="Kosongkan jika tidak mahu tukar" autocomplete="new-password" />
                        <button type="button" wire:click="autoPassword" class="mt-2 inline-flex min-h-9 items-center gap-1.5 text-[12px] font-semibold text-primary"><i class="ph ph-sparkle text-[14px]"></i> Auto-jana kata laluan selamat</button>
                    </div>
                @endif
            @else
                <div class="col-span-full">
                    <x-ui.field label="Kata Laluan Sementara" wire:model="tempPassword" placeholder="Auto-jana atau taip" autocomplete="off" />
                    <button type="button" wire:click="autoPassword" class="mt-2 inline-flex min-h-9 items-center gap-1.5 text-[12px] font-semibold text-primary"><i class="ph ph-sparkle text-[14px]"></i> Auto-jana kata laluan selamat</button>
                </div>
            @endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
            <x-ui.button type="submit" form="user-form" icon="check">{{ $editingId ? 'Simpan' : 'Simpan Pengguna' }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- ===================== MODAL: Akses & Keselamatan ===================== --}}
    <x-ui.modal wire:model="showAccess" title="Akses & Keselamatan" :subtitle="$this->accessUser?->name" icon="lock-key" tone="danger" max-width="420px" body-class="flex flex-col gap-[10px] px-4 py-5 md:px-6">
        @if ($accessMessage)
            <div class="flex items-center gap-2 rounded-[10px] bg-success-soft px-[14px] py-3 text-[13px] font-semibold text-success" role="status"><i class="ph-fill ph-check-circle text-[17px]"></i> {{ $accessMessage }}</div>
        @endif
        @error('status')<div class="rounded-[10px] bg-danger-soft px-[14px] py-3 text-[13px] font-semibold text-danger">{{ $message }}</div>@enderror

        <button type="button" wire:click="sendReset" class="flex min-h-12 w-full items-center gap-[10px] rounded-[10px] border border-border bg-bg px-[15px] py-[13px] text-left text-[13.5px] font-semibold text-ink-2">
            <i class="ph ph-arrow-counter-clockwise text-[18px] text-primary"></i> Set semula kata laluan
        </button>
        <button type="button" wire:click="forceChange" class="flex min-h-12 w-full items-center gap-[10px] rounded-[10px] border border-border bg-bg px-[15px] py-[13px] text-left text-[13.5px] font-semibold text-ink-2">
            <i class="ph ph-key text-[18px] text-primary"></i> Paksa tukar kata laluan
        </button>
        @if ($this->accessUser?->isSuspended())
            <button type="button" wire:click="toggleSuspend" class="flex min-h-12 w-full items-center gap-[10px] rounded-[10px] border border-[#BBE5C9] bg-success-soft px-[15px] py-[13px] text-left text-[13.5px] font-semibold text-success">
                <i class="ph ph-lock-simple-open text-[18px]"></i> Aktifkan semula akaun
            </button>
        @else
            <button type="button" wire:click="toggleSuspend" wire:confirm="Gantung akaun ini? Semua sesi aktif akan ditamatkan."
                    class="flex min-h-12 w-full items-center gap-[10px] rounded-[10px] border border-[#F7CFCF] bg-danger-soft px-[15px] py-[13px] text-left text-[13.5px] font-semibold text-danger">
                <i class="ph ph-lock-simple text-[18px]"></i> Gantung / Kunci akaun
            </button>
        @endif
    </x-ui.modal>
</div>
