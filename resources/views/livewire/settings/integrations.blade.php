<div>
    <x-ui.page-header title="Integrasi API" subtitle="Sambungan gerbang pembayaran & perkhidmatan luar." :breadcrumb="['Tetapan', 'Integrasi API']" />

    <form wire:submit="save" class="flex max-w-[820px] flex-col gap-[22px]" novalidate>
        <fieldset @disabled(! $canManage)>
            <section class="rounded-[12px] border border-border bg-surface p-5 md:p-6">
                <div class="mb-[18px] flex flex-wrap items-center gap-[13px]">
                    <div class="flex size-[52px] shrink-0 items-center justify-center rounded-[12px] bg-primary-soft text-primary"><i class="ph ph-credit-card text-[26px]"></i></div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[15px] font-bold text-primary dark:text-[#c9ce93]">CHIP Collect</div>
                        <div class="mt-0.5 text-[12px] text-faint">Portal Bayaran Ansuran — FPX, DuitNow QR, Kad, E-Wallet</div>
                    </div>
                    @if ($fake)
                        <x-ui.badge tone="warning" icon="flask">Mod Simulasi (tempatan)</x-ui.badge>
                    @elseif ($configured)
                        <x-ui.badge tone="success" icon="fill check-circle">Disambung</x-ui.badge>
                    @else
                        <x-ui.badge tone="neutral">Belum dikonfigurasi</x-ui.badge>
                    @endif
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-ui.field label="Brand ID" wire:model="brandId" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" autocomplete="off" span />
                    <x-ui.field label="Secret Key" hint="{{ $hasSecret ? '(tersimpan — biarkan kosong untuk kekal)' : '' }}" type="password" wire:model="secretKey" autocomplete="new-password" :placeholder="$hasSecret ? '••••••••••••••••' : 'Secret API key'" span />
                    <x-ui.field label="Kunci Awam Webhook (PEM)" hint="(pilihan)" as="textarea" rows="4" wire:model="webhookPublicKey" placeholder="-----BEGIN PUBLIC KEY-----" class="font-mono !text-[12px]" span />
                </div>
                <div class="mt-4 rounded-[10px] bg-bg px-[14px] py-3 text-[12.5px] text-ink-3">
                    <div class="mb-1 font-semibold text-ink-2">URL Callback / Webhook</div>
                    <code class="font-mono text-[12px] break-all text-ink">{{ $webhookUrl }}</code>
                    <p class="mt-1.5 text-[11.5px] text-faint">Daftar URL ini sebagai webhook (acara <code>purchase.paid</code> &amp; <code>purchase.payment_failure</code>) dalam portal CHIP. Setiap panggilan disahkan dengan tandatangan RSA (X-Signature). Mod ujian/langsung ditentukan oleh Secret Key.</p>
                </div>
            </section>
        </fieldset>
        @if ($canManage)
            <div class="flex justify-end">
                <x-ui.button type="submit" icon="check" class="max-md:w-full">Simpan</x-ui.button>
            </div>
        @endif
    </form>
</div>
