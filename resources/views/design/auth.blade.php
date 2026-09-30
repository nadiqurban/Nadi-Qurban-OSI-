<x-layouts::auth title="Log Masuk" :stats="[['value' => '7', 'label' => 'Negara Pelaksanaan'], ['value' => '42', 'label' => 'Rakan Vendor'], ['value' => '6,540', 'label' => 'Peserta 2027']]">
    <div class="text-[13px] font-semibold tracking-[.5px] text-gold">SELAMAT KEMBALI</div>
    <h2 class="mt-2 text-[24px] font-extrabold text-ink md:text-[26px]">Log Masuk Akaun</h2>
    <p class="mt-2 text-[14px] text-muted">Masukkan kelayakan anda untuk mengakses sistem operasi.</p>
    <div class="mt-7 flex flex-col gap-4">
        <x-ui.field label="Emel" type="email" placeholder="nama@nadiqurban.com" />
        <x-ui.field label="Kata Laluan" type="password" placeholder="••••••••" />
        <x-ui.button block>Log Masuk</x-ui.button>
    </div>
    <p class="mt-6 text-[12px] text-faint">Pratonton layout auth (Fasa 0). Skrin log masuk sebenar dibina dalam Fasa 1.</p>
</x-layouts::auth>
