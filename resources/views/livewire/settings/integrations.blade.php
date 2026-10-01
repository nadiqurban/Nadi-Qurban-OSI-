@php
    $gw = $gateway ? \App\Support\PaymentGateways::GATEWAYS[$gateway] : null;
    $hasSecret = fn (string $key) => (string) app(\App\Support\Settings::class)->get($key) !== '';
@endphp

<div>
    <x-ui.page-header title="Integrasi API" subtitle="Urus kunci API, sambungan pihak ketiga & had penggunaan." :breadcrumb="['Tetapan', 'Integrasi API']">
        @if ($canManage)
            <x-slot:actions>
                <x-ui.button icon="plus" id="btn-new-key" wire:click="openKey" mobile-block>Jana Kunci API</x-ui.button>
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
            <div class="border-b border-divider px-5 py-4 text-[14px] font-bold text-ink">Kunci API</div>
            @forelse ($this->clients as $c)
                @php $last = $c->tokens->max('last_used_at'); @endphp
                <div wire:key="key-{{ $c->id }}" class="border-b border-divider px-5 py-[14px] last:border-b-0">
                    <div class="flex items-center justify-between gap-3">
                        <span class="min-w-0 truncate text-[13px] font-semibold text-ink">{{ $c->name }}</span>
                        <span class="flex shrink-0 items-center gap-2">
                            <x-ui.badge :tone="$c->environment === 'live' ? 'success' : 'info'" class="!px-[10px] !text-[11px]">{{ $c->environment === 'live' ? 'Aktif' : 'Ujian' }}</x-ui.badge>
                            @if ($canManage)
                                <button type="button" wire:click="revoke({{ $c->id }})" wire:confirm="Batalkan kunci {{ $c->name }}? Sistem yang menggunakannya akan berhenti berfungsi." class="flex size-7 items-center justify-center rounded-[7px] text-danger hover:bg-danger-soft max-md:size-11" aria-label="Batalkan {{ $c->name }}"><i class="ph ph-trash text-[15px]"></i></button>
                            @endif
                        </span>
                    </div>
                    <div class="mt-1.5 font-mono text-[12px] text-muted">{{ $c->prefix }}&bull;&bull;&bull;&bull;&bull;&bull;</div>
                    <div class="mt-[7px] flex flex-wrap items-center gap-x-[14px] gap-y-1 text-[11.5px] text-faint">
                        <span><i class="ph ph-calendar-blank text-[13px] align-[-1px]"></i> Dicipta {{ tarikh($c->created_at) }}</span>
                        <span><i class="ph ph-clock text-[13px] align-[-1px]"></i> Guna {{ $last ? masa_lalu($last) : 'belum pernah' }}</span>
                        <span><i class="ph ph-shield-check text-[13px] align-[-1px]"></i> {{ implode(' + ', $c->tokens->first()->abilities ?? []) }}</span>
                    </div>
                </div>
            @empty
                <x-ui.empty-state icon="key" title="Belum ada kunci API." />
            @endforelse
        </section>

        <section class="min-w-0 rounded-[12px] border border-border bg-surface p-5" x-data="{ copied: false }">
            <div class="mb-1.5 text-[13px] font-bold text-ink">Base URL</div>
            <div class="flex gap-2">
                <input x-ref="base" value="{{ $baseUrl }}" readonly aria-label="Base URL" class="min-w-0 flex-1 rounded-[9px] border border-border bg-bg px-[11px] py-[9px] font-mono text-[12px] text-ink outline-none max-md:min-h-11">
                <x-ui.button size="sm" class="!px-[13px] !text-[12px]" x-on:click="navigator.clipboard.writeText($refs.base.value); copied = true; setTimeout(() => copied = false, 1500)"><span x-text="copied ? 'Disalin' : 'Salin'">Salin</span></x-ui.button>
            </div>
            <p class="mt-2 text-[11.5px] leading-[1.5] text-faint">Sertakan header <b>Authorization: Bearer &lt;API_KEY&gt;</b> pada setiap permintaan. Rujukan penuh: <code>docs/api.md</code>.</p>
            <div class="my-4 h-px bg-divider"></div>
            <div class="mb-[10px] text-[13px] font-bold text-ink">Had Kadar</div>
            <div class="flex items-center justify-between text-[12.5px] text-ink-3"><span>{{ \App\Livewire\Settings\Integrations::RATE_LIMIT }} permintaan / minit</span><span class="font-bold text-primary dark:text-[#c9ce93]">{{ $usagePct }}% digunakan</span></div>
            <div class="mt-2 h-2 overflow-hidden rounded-[20px] bg-divider"><div class="h-full bg-primary" style="width: {{ $usagePct }}%"></div></div>
        </section>
    </div>

    <section class="mt-[22px] overflow-hidden rounded-[12px] border border-border bg-surface">
        <div class="border-b border-divider px-5 py-4 text-[14px] font-bold text-ink">Sambungan Pihak Ketiga</div>
        @foreach ($connections as $c)
            <div class="flex items-center gap-[14px] border-b border-divider px-5 py-[14px] last:border-b-0">
                <div class="flex size-[42px] shrink-0 items-center justify-center rounded-[10px] {{ \App\Support\Tone::classes($c['tone']) }}"><i class="ph ph-{{ $c['icon'] }} text-[22px]"></i></div>
                <div class="min-w-0 flex-1"><div class="text-[13.5px] font-semibold text-ink">{{ $c['name'] }}</div><div class="mt-0.5 text-[12px] text-faint">{{ $c['desc'] }}</div></div>
                <x-ui.badge :tone="$c['active'] ? 'success' : 'neutral'" class="shrink-0 !px-[11px] !text-[11px]">{{ $c['active'] ? 'Aktif' : 'Tidak Aktif' }}</x-ui.badge>
            </div>
        @endforeach
    </section>

    <section class="mt-[22px] overflow-hidden rounded-[12px] border border-border bg-surface">
        <div class="border-b border-divider px-5 py-4 text-[14px] font-bold text-ink">Gerbang Pembayaran &mdash; Kutipan (Collect Payment)</div>
        @foreach (\App\Support\PaymentGateways::GATEWAYS as $key => [$label, $icon, $tone, $desc])
            @php [$status, $statusTone] = $gateways->status($key); $on = $gateways->enabled($key); @endphp
            <div class="flex flex-wrap items-center gap-[14px] border-b border-divider px-5 py-4 last:border-b-0">
                <div class="flex size-[46px] shrink-0 items-center justify-center rounded-[11px] {{ \App\Support\Tone::classes($tone) }}"><i class="ph ph-{{ $icon }} text-[24px]"></i></div>
                <div class="min-w-0 flex-1">
                    <div class="text-[14px] font-bold text-ink">{{ $label }}
                        <x-ui.badge :tone="$statusTone" class="ml-1.5 !px-[9px] !py-[3px] !text-[11px]">{{ $status }}</x-ui.badge>
                        @if ($key === 'chip' && $chipFake)<x-ui.badge tone="warning" icon="flask" class="ml-1 !px-[9px] !py-[3px] !text-[11px]">Simulasi</x-ui.badge>@endif
                    </div>
                    <div class="mt-[3px] text-[12px] text-faint">{{ $desc }}</div>
                </div>
                @if ($canManage)
                    <div class="flex gap-2 max-md:w-full">
                        <x-ui.button size="sm" icon="gear-six" id="gw-{{ $key }}" wire:click="openGateway('{{ $key }}')" class="!px-4 !py-[10px] !text-[13px] max-md:flex-1">Tetapan</x-ui.button>
                        <x-ui.button size="sm" :variant="$on ? 'secondary' : 'soft'" :icon="$on ? 'prohibit' : 'power'" id="gw-toggle-{{ $key }}" wire:click="toggleGateway('{{ $key }}')"
                                     :class="'!px-[14px] !py-[10px] !text-[13px] max-md:flex-1 '.($on ? '!border-[#F7CFCF] !text-danger' : '!border !border-[#BFE5CC] !bg-success-soft !text-success')">{{ $on ? 'Nyahaktif' : 'Aktifkan' }}</x-ui.button>
                    </div>
                @endif
            </div>
        @endforeach
    </section>

    @if ($canManage)
        {{-- Jana Kunci API (token shown once) --}}
        <x-ui.modal wire:model="showKey" title="Jana Kunci API" subtitle="Kunci untuk sistem luar mengakses /api/v1" icon="key" max-width="520px" :footer-border="false">
            @if ($newToken)
                <div x-data="{ copied: false }" class="flex flex-col gap-3">
                    <div class="flex items-start gap-2 rounded-[10px] bg-warning-soft px-[14px] py-3 text-[12.5px] text-[#92400E]"><i class="ph-fill ph-warning text-[17px] text-warning"></i><span>Salin kunci ini sekarang. Atas sebab keselamatan ia <b>tidak akan dipaparkan lagi</b>.</span></div>
                    <label class="text-[12px] font-semibold text-ink-2" for="new-token">Kunci API Rasmi</label>
                    <div class="flex gap-2">
                        <input id="new-token" x-ref="token" value="{{ $newToken }}" readonly class="min-w-0 flex-1 rounded-[9px] border border-border bg-bg px-[11px] py-[9px] font-mono text-[12px] text-ink outline-none max-md:min-h-11">
                        <x-ui.button size="sm" icon="copy" x-on:click="navigator.clipboard.writeText($refs.token.value); copied = true"><span x-text="copied ? 'Disalin' : 'Salin'">Salin</span></x-ui.button>
                    </div>
                </div>
                <x-slot:footer>
                    <x-ui.button x-on:click="open = false">Selesai</x-ui.button>
                </x-slot:footer>
            @else
                <div class="flex flex-col gap-4">
                    <x-ui.field label="Nama Kunci" id="key-name" wire:model="keyName" placeholder="cth. Sistem ERP Dalaman" />
                    <div>
                        <span class="mb-1.5 block text-[12px] font-semibold text-ink-2">Persekitaran</span>
                        <div class="flex gap-2">
                            @foreach (['live' => 'Produksi', 'test' => 'Ujian / Sandbox'] as $k => $l)
                                <button type="button" wire:click="$set('keyEnv', '{{ $k }}')" @class(['flex-1 rounded-[9px] border px-3 py-[9px] text-[12.5px] font-semibold max-md:min-h-11', 'border-primary bg-primary-soft text-primary dark:text-[#c9ce93]' => $keyEnv === $k, 'border-border text-muted' => $keyEnv !== $k])>{{ $l }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <span class="mb-1.5 block text-[12px] font-semibold text-ink-2">Kebenaran</span>
                        <div class="flex flex-col gap-2">
                            <x-ui.checkbox value="read" wire:model="keyAbilities" label="Baca — produk, tempahan & status (GET)" />
                            <x-ui.checkbox value="write" wire:model="keyAbilities" label="Tulis — cipta tempahan (POST /orders)" />
                        </div>
                        @error('keyAbilities')<p class="mt-1 text-[11.5px] text-danger">{{ $message }}</p>@enderror
                    </div>
                </div>
                <x-slot:footer>
                    <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
                    <x-ui.button icon="key" id="btn-create-key" wire:click="createKey">Jana Kunci</x-ui.button>
                </x-slot:footer>
            @endif
        </x-ui.modal>

        {{-- Gateway settings --}}
        <x-ui.modal wire:model="showGateway" :title="$gw ? 'Tetapan '.$gw[0] : 'Tetapan'" subtitle="Gerbang kutipan pembayaran" :icon="$gw[1] ?? 'gear'" :tone="$gw[2] ?? 'primary'" max-width="480px" :footer-border="false">
            @if ($gw)
                <div class="flex flex-col gap-4">
                    @php
                        $secretInput = function (string $label, string $model, string $storedKey, string $placeholder) use ($hasSecret) {
                            return compact('label', 'model', 'storedKey', 'placeholder') + ['stored' => $hasSecret($storedKey)];
                        };
                        $secrets = match ($gateway) {
                            'chip' => [$secretInput('Secret Key', 'secret', 'chip.secret_key', 'sk_live_••••••••')],
                            'billplz' => [$secretInput('API Secret Key', 'secret', 'billplz.secret_key', 'cth. 9f8e7d6c-5b4a-...')],
                            default => [$secretInput('Secret Key', 'secret', 'toyyibpay.secret_key', 'cth. abcd1234-ef56-...')],
                        };
                    @endphp

                    @if ($gateway === 'chip')
                        <x-ui.field label="Brand ID" id="gw-brand" wire:model="form.brand_id" placeholder="cth. 88f3a1c2-9e40-4d1b-b0a7-1234567890ab" autocomplete="off" />
                    @endif

                    @foreach ($secrets as $s)
                        <div x-data="{ show: false }">
                            <label class="mb-1.5 block text-[12px] font-semibold text-ink-2" for="gw-secret">{{ $s['label'] }} @if ($s['stored'])<span class="font-normal text-faint">(tersimpan — biarkan kosong untuk kekal)</span>@endif</label>
                            <div class="flex gap-2">
                                <input id="gw-secret" :type="show ? 'text' : 'password'" wire:model="{{ $s['model'] }}" autocomplete="new-password" placeholder="{{ $s['stored'] ? '••••••••••••••••' : $s['placeholder'] }}"
                                       class="min-w-0 flex-1 rounded-[9px] border border-border bg-surface px-3 py-[10px] text-[13px] text-ink outline-none focus:border-primary max-md:min-h-11 max-md:text-[16px]">
                                <button type="button" @click="show = !show" class="shrink-0 rounded-[9px] border border-border px-3 text-[12px] font-semibold text-ink-2 max-md:min-h-11" x-text="show ? 'Sorok' : 'Papar'">Papar</button>
                            </div>
                            @error($s['model'])<p class="mt-1 text-[11.5px] text-danger">{{ $message }}</p>@enderror
                        </div>
                    @endforeach

                    @if ($gateway === 'chip')
                        <div>
                            <span class="mb-1.5 block text-[12px] font-semibold text-ink-2">Kaedah Pembayaran <span class="font-normal text-faint">(klik untuk hidup/mati)</span></span>
                            <div class="flex flex-wrap gap-2">
                                @foreach (\App\Support\PaymentGateways::CHIP_METHODS as $m => $mi)
                                    @php $mOn = in_array($m, $chipMethods, true); @endphp
                                    <button type="button" wire:click="toggleChipMethod('{{ $m }}')" aria-pressed="{{ $mOn ? 'true' : 'false' }}" @class(['inline-flex items-center gap-1.5 rounded-[20px] px-3 py-1.5 text-[12px] font-semibold max-md:min-h-10', 'bg-primary-soft text-primary dark:text-[#c9ce93]' => $mOn, 'bg-divider text-faint line-through' => ! $mOn])><i class="ph ph-{{ $mi }} text-[14px]"></i>{{ $m }}</button>
                                @endforeach
                            </div>
                            @error('chipMethods')<p class="mt-1 text-[11.5px] text-danger">{{ $message }}</p>@enderror
                        </div>
                        <x-ui.field label="Kunci Awam Webhook (PEM)" hint="(pilihan)" as="textarea" rows="3" wire:model="form.webhook_public_key" error="form.webhook_public_key" placeholder="-----BEGIN PUBLIC KEY-----" class="font-mono !text-[12px]" />
                        <p class="rounded-[9px] bg-bg px-3 py-[10px] text-[11.5px] leading-[1.5] text-faint">URL callback: <code class="break-all text-ink-2">{{ $chipWebhookUrl }}</code> (acara <code>purchase.paid</code> &amp; <code>purchase.payment_failure</code>).</p>
                    @elseif ($gateway === 'billplz')
                        <x-ui.field label="Collection ID" wire:model="form.collection_id" error="form.collection_id" placeholder="cth. inbmmepb" />
                        <div x-data="{ show: false }">
                            <label class="mb-1.5 block text-[12px] font-semibold text-ink-2" for="gw-sig">X-Signature Key <span class="font-normal text-faint">{{ $hasSecret('billplz.x_signature') ? '(tersimpan)' : '(opsyenal)' }}</span></label>
                            <div class="flex gap-2">
                                <input id="gw-sig" :type="show ? 'text' : 'password'" wire:model="secret2" autocomplete="new-password" placeholder="Kunci pengesahan callback (opsyenal)" class="min-w-0 flex-1 rounded-[9px] border border-border bg-surface px-3 py-[10px] text-[13px] text-ink outline-none focus:border-primary max-md:min-h-11 max-md:text-[16px]">
                                <button type="button" @click="show = !show" class="shrink-0 rounded-[9px] border border-border px-3 text-[12px] font-semibold text-ink-2 max-md:min-h-11" x-text="show ? 'Sorok' : 'Papar'">Papar</button>
                            </div>
                        </div>
                    @else
                        <x-ui.field label="Category Code" wire:model="form.category_code" error="form.category_code" placeholder="cth. 7hk2mabc" />
                        <div>
                            <span class="mb-1.5 block text-[12px] font-semibold text-ink-2">Persekitaran</span>
                            <div class="flex gap-2">
                                @foreach (['sandbox' => 'Sandbox', 'production' => 'Production'] as $k => $l)
                                    <button type="button" id="tp-env-{{ $k }}" wire:click="$set('form.env', '{{ $k }}')" @class(['flex-1 rounded-[9px] border px-3 py-[9px] text-[12.5px] font-semibold max-md:min-h-11', 'border-primary bg-primary-soft text-primary dark:text-[#c9ce93]' => ($form['env'] ?? '') === $k, 'border-border text-muted' => ($form['env'] ?? '') !== $k])>{{ $l }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <p class="rounded-[9px] bg-bg px-3 py-[10px] text-[11.5px] leading-[1.5] text-faint"><i class="ph ph-info align-[-2px] text-[13px] text-success"></i> Endpoint: <b>{{ $gateway === 'toyyibpay' ? (($form['env'] ?? '') === 'sandbox' ? 'https://dev.toyyibpay.com' : 'https://toyyibpay.com') : $gateways->endpoint($gateway) }}</b></p>
                </div>
            @endif
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button icon="check" id="btn-save-gw" wire:click="saveGateway">Simpan &amp; Sambung</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
