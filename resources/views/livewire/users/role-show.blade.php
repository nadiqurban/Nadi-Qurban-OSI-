<div>
    <a href="{{ route('users.index', ['tab' => 'peranan']) }}" wire:navigate class="mb-[18px] inline-flex min-h-11 items-center gap-[7px] text-[13.5px] font-semibold text-primary"><i class="ph ph-arrow-left text-[17px]"></i> Kembali ke peranan</a>

    <div class="mb-5 flex flex-wrap items-center gap-5 rounded-[14px] border border-border bg-surface p-5 md:p-6">
        <div class="flex size-[60px] items-center justify-center rounded-[15px] {{ \App\Support\Tone::classes($role->tone ?? 'neutral') }}"><i class="ph ph-{{ $role->icon ?? 'shield' }} text-[30px]"></i></div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-[20px] font-bold text-ink md:text-[22px]">{{ $role->name }}</h1>
                <span class="rounded-[20px] bg-bg px-[11px] py-[3px] text-[11.5px] font-bold text-muted">{{ $members->count() }} ahli</span>
            </div>
            <p class="mt-1.5 max-w-[560px] text-[13px] leading-[1.5] text-muted">{{ $role->description }}</p>
        </div>
        @if ($canManage)
            <x-ui.button icon="pencil-simple" mobile-block wire:click="openEdit">Edit Peranan</x-ui.button>
        @endif
    </div>

    <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
        {{-- members --}}
        <section class="min-w-0 overflow-hidden rounded-[12px] border border-border bg-surface">
            <div class="flex items-center justify-between px-[22px] pt-[18px] pb-[14px]">
                <h2 class="text-[15px] font-bold text-ink">Ahli Peranan</h2>
                @if ($canManage)
                    <a href="{{ route('users.index') }}" wire:navigate class="text-[12.5px] font-semibold text-primary">+ Tambah</a>
                @endif
            </div>
            @forelse ($members as $u)
                <div class="flex items-center gap-3 border-t border-divider px-[22px] py-3" wire:key="mem-{{ $u->id }}">
                    <x-ui.avatar :name="$u->name" :tone="\App\Support\Tone::avatarFor($u->id - 1)" :size="36" />
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[13px] font-semibold text-ink">{{ $u->name }}</span>
                        <span class="block truncate text-[11.5px] text-faint">{{ $u->email }}</span>
                    </span>
                    <x-ui.status-badge :status="$u->status" />
                </div>
            @empty
                <div class="border-t border-divider"><x-ui.empty-state icon="users" title="Tiada ahli" /></div>
            @endforelse
        </section>

        {{-- permissions --}}
        <section class="min-w-0 overflow-hidden rounded-[12px] border border-border bg-surface">
            <div class="px-[22px] pt-[18px] pb-[14px]">
                <h2 class="text-[15px] font-bold text-ink">Kebenaran Modul</h2>
                <p class="mt-[3px] text-[12.5px] text-muted">Tahap akses peranan ini bagi setiap modul</p>
            </div>
            @foreach ($perms as $p)
                <div class="flex items-center gap-3 border-t border-divider px-[22px] py-[13px]">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-[8px] bg-bg text-muted"><i class="ph ph-{{ $p['module']->icon() }} text-[17px]"></i></span>
                    <span class="flex-1 text-[13px] font-semibold text-ink-2">{{ $p['module']->label() }}</span>
                    <span class="inline-flex items-center gap-[5px] rounded-[20px] px-[11px] py-1 text-[11.5px] font-bold whitespace-nowrap {{ $p['level']->pillClasses() }}"><i class="{{ $p['level']->icon() }} text-[15px]"></i> {{ $p['level']->label() }}</span>
                </div>
            @endforeach
        </section>
    </div>

    {{-- Edit Peranan --}}
    <x-ui.modal wire:model="showEdit" title="Edit Peranan" :subtitle="$role->name" icon="pencil-simple" max-width="460px" :footer-border="false">
        <form id="role-form" wire:submit="save" class="flex flex-col gap-4" novalidate>
            <x-ui.field label="Nama Peranan" wire:model="name" :readonly="(bool) $role->roleName()"
                        :class="$role->roleName() ? 'bg-bg text-faint' : ''" />
            <x-ui.field label="Keterangan" as="textarea" rows="3" wire:model="description" />
            <div>
                <span class="mb-2 block text-[12px] font-semibold text-ink-2">Kebenaran Modul <span class="font-normal text-faint">(klik lencana untuk tukar)</span></span>
                @if ($role->isLockedMatrix())
                    <p class="mb-2 text-[11.5px] text-faint">Super Admin sentiasa mempunyai akses penuh ke semua modul.</p>
                @endif
                <div class="max-h-[230px] overflow-y-auto rounded-[10px] border border-border max-md:max-h-none">
                    @foreach (\App\Enums\Module::cases() as $m)
                        @php $lvl = \App\Enums\AccessLevel::from($levels[$m->value] ?? 'N'); @endphp
                        <div class="flex items-center gap-[10px] border-b border-divider px-[13px] py-[10px] last:border-b-0">
                            <span class="flex size-[26px] shrink-0 items-center justify-center rounded-[7px] bg-bg text-muted"><i class="ph ph-{{ $m->icon() }} text-[15px]"></i></span>
                            <span class="flex-1 text-[12.5px] text-ink-2">{{ $m->label() }}</span>
                            <button type="button" wire:click="cycle('{{ $m->value }}')" @disabled($role->isLockedMatrix())
                                    class="inline-flex min-h-8 items-center gap-[5px] rounded-[20px] px-[11px] py-1 text-[11.5px] font-bold whitespace-nowrap disabled:cursor-not-allowed {{ $lvl->pillClasses() }}">
                                <i class="{{ $lvl->icon() }} text-[13px]"></i> {{ $lvl->label() }}
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
            <x-ui.button type="submit" form="role-form" icon="check">Simpan Peranan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
