@php
    $orders = [
        ['NQ-QB-LE-001248', 'Ahmad Zaki bin Hassan', '012-3456789', 'Qurban', 'Lembu', 'Delima', 'Uganda', 1, 245000, 'FPX · Berjaya', 'success', 'Dalam Proses', false],
        ['NQ-AQ-KA-001247', 'Nurul Aina binti Rahim', '013-9988776', 'Aqiqah', 'Kambing', 'Mutiara', 'Malaysia', 1, 85000, 'Pindahan Bank', 'primary', 'Selesai', true],
        ['NQ-QB-UN-001246', 'Mohd Firdaus bin Omar', '019-2345671', 'Qurban', 'Unta', 'Topaz', 'Arab Saudi', 1, 520000, 'FPX · Gagal', 'danger', 'Menunggu Bayaran', false],
        ['NQ-DM-KA-001245', 'Siti Khadijah bt Yusof', '017-6543210', 'Dam', 'Kambing', 'Zamrud', 'Arab Saudi', 1, 72000, 'Cek', 'primary', 'Draf', false],
        ['NQ-QB-LE-001243', 'Rosmah binti Idris', '012-8877665', 'Qurban', 'Lembu', 'Delima', 'Nigeria', 7, 1715000, 'FPX · Berjaya', 'success', 'Dibatalkan', false],
    ];
    $flow = ['Tempahan Diterima', 'Pengesahan Bayaran', 'Akad', 'Assign Negara', 'Assign Vendor', 'Pelaksanaan', 'Upload Laporan', 'HQ Verify', 'Generate Final Report', 'Generate AWB', 'Sijil Dipos', 'Completed'];
    $times = ['12 Jun, 09:14', '12 Jun, 14:20', '13 Jun, 10:00', '14 Jun, 08:30', '15 Jun, 16:45'];
    $steps = collect($flow)->map(fn ($label, $i) => ['label' => $label, 'state' => $i < 5 ? 'done' : ($i === 5 ? 'current' : 'pending'), 'time' => $times[$i] ?? null])->all();
    $cols = '36px 1.4fr 1.5fr 0.9fr 1fr 0.9fr 0.8fr 1fr 1.1fr 1fr 0.7fr';
@endphp

<x-layouts::app title="Komponen UI">
    <x-ui.page-header title="Sistem Reka Bentuk" subtitle="Semua komponen UI dalam setiap keadaan — semak pada 1440px dan 390px." :breadcrumb="['Dalaman', 'Komponen UI']">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="download-simple">Eksport</x-ui.button>
            <x-ui.button variant="success" icon="microsoft-excel-logo">Eksport Excel</x-ui.button>
            <x-ui.button icon="plus" mobile-block x-data @click="$dispatch('open-modal', 'demo')">Tempahan Baharu</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex flex-col gap-8">

        {{-- Buttons --}}
        <x-ui.card title="Butang" subtitle="primary · secondary · success · gold · gold-outline · danger · soft · ghost · icon-only · sm">
            <div class="flex flex-wrap gap-3">
                <x-ui.button icon="plus">Tempahan Baharu</x-ui.button>
                <x-ui.button variant="secondary" icon="download-simple">Eksport</x-ui.button>
                <x-ui.button variant="success" icon="microsoft-excel-logo">Eksport Excel</x-ui.button>
                <x-ui.button variant="gold" icon="users-three">Jana Senarai Peserta</x-ui.button>
                <x-ui.button variant="gold-outline" icon="receipt">Resit</x-ui.button>
                <x-ui.button variant="danger" icon="trash">Buang</x-ui.button>
                <x-ui.button variant="danger-soft" icon="x">Reset</x-ui.button>
                <x-ui.button variant="soft" icon="arrow-square-out">Buka dalam tab baharu</x-ui.button>
                <x-ui.button variant="ghost" icon="arrow-left">Kembali</x-ui.button>
                <x-ui.button disabled icon="fill check-circle">Sahkan Akad</x-ui.button>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <x-ui.button size="sm" variant="success" icon="fill check-circle">Sahkan</x-ui.button>
                <x-ui.button size="sm" variant="danger-soft" icon="x-circle">Batal</x-ui.button>
                <x-ui.button variant="secondary" icon-only icon="eye" aria-label="Lihat" />
                <x-ui.button variant="secondary" icon-only size="sm" icon="pencil-simple" aria-label="Edit" />
                <x-ui.button variant="gold-outline" icon-only size="sm" icon="receipt" aria-label="Resit" />
            </div>
        </x-ui.card>

        {{-- Stat & KPI cards --}}
        <div>
            <h2 class="mb-3 text-[16px] font-bold text-ink">Kad Statistik</h2>
            <div class="grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
                <x-ui.stat-card icon="shopping-cart-simple" value="248" label="Jumlah Tempahan" />
                <x-ui.stat-card icon="clock" tone="warning" value="34" label="Menunggu Bayaran" />
                <x-ui.stat-card icon="check-circle" tone="success" value="186" label="Selesai" />
                <x-ui.stat-card icon="money" tone="gold" value="{{ rm_short(38200000) }}" label="Jumlah Jualan" />
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3 md:gap-[18px] lg:grid-cols-4">
                <x-ui.kpi-card icon="shopping-cart-simple" value="1,248" label="Jumlah Tempahan" delta="↑ 12.4%" />
                <x-ui.kpi-card icon="money" tone="gold" value="{{ rm_short(382000000) }}" label="Jumlah Jualan" delta="↑ 15.2%" />
                <x-ui.kpi-card icon="truck" value="42" label="Jumlah Vendor" delta="+2 baharu" trend="flat" />
                <x-ui.kpi-card icon="wallet" tone="danger" value="87" label="Bayaran Tertunggak" delta="↓ 4.5%" trend="down" />
                <x-ui.kpi-card icon="folders" tone="warning" value="34" label="Laporan Tertunggak" delta="↑ 6" trend="warn" />
                <x-ui.kpi-card icon="calendar-check" tone="info" value="5" label="Pelaksanaan Akan Datang" delta="30 hari" trend="info" />
            </div>
        </div>

        {{-- Badges --}}
        <x-ui.card title="Lencana">
            <div class="flex flex-wrap items-center gap-2">
                @foreach (['Draf', 'Menunggu Bayaran', 'Diterima', 'Dalam Proses', 'Selesai', 'Dibatalkan'] as $s)
                    <x-ui.status-badge :status="$s" />
                @endforeach
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-ui.badge tone="purple" variant="label" icon="calendar-check">ANSURAN</x-ui.badge>
                <x-ui.badge tone="gold-ink" variant="tag">Platinum</x-ui.badge>
                <x-ui.badge tone="gold" variant="tag">Emas</x-ui.badge>
                <x-ui.badge tone="primary" variant="tag">Perak</x-ui.badge>
                <x-ui.badge tone="success" variant="tag">FPX · Berjaya</x-ui.badge>
                <x-ui.badge tone="danger" variant="tag">FPX · Gagal</x-ui.badge>
                <x-ui.badge tone="info">Info</x-ui.badge>
                <x-ui.badge tone="warning">Amaran</x-ui.badge>
                <x-ui.badge tone="danger">Kritikal</x-ui.badge>
            </div>
        </x-ui.card>

        {{-- Tabs, checkbox, avatars --}}
        <div class="grid gap-4 lg:grid-cols-2">
            <x-ui.card title="Tab & Pill">
                <div x-data="{ tab: 'pending', period: 'Harian' }" class="flex flex-col gap-4">
                    <x-ui.tabs alpine="tab" :items="[['key' => 'pending', 'label' => 'Belum Disahkan', 'count' => 5], ['key' => 'done', 'label' => 'Telah Disahkan', 'count' => 12]]" />
                    <x-ui.tabs alpine="period" variant="pill" :items="collect(['Harian', 'Mingguan', 'Bulanan', 'Tahunan', 'Custom'])->map(fn ($l) => ['key' => $l, 'label' => $l])->all()" />
                    <x-ui.tabs alpine="tab" :items="[['key' => 'a', 'label' => 'Semua', 'count' => 248], ['key' => 'b', 'label' => 'Berjalan', 'count' => 96], ['key' => 'c', 'label' => 'Selesai', 'count' => 120], ['key' => 'd', 'label' => 'Lewat Bayar', 'count' => 18], ['key' => 'e', 'label' => 'Batal', 'count' => 14]]" />
                </div>
            </x-ui.card>
            <x-ui.card title="Checkbox & Avatar">
                <div class="flex flex-wrap items-center gap-4">
                    <x-ui.checkbox />
                    <x-ui.checkbox checked />
                    <x-ui.checkbox label="Saya mengesahkan lafaz akad telah dibuat" checked />
                    <x-ui.checkbox :size="18" checked />
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <x-ui.avatar name="Muhammad Nurfitkri" tone="brand" :size="40" />
                    @foreach (['Aisyah Rahman', 'Fatimah Zahra', 'Hakim Rosli', 'Salmah binti Idris', 'Kamal Ariff', 'Zulkifli Aziz'] as $i => $n)
                        <x-ui.avatar :name="$n" :tone="\App\Support\Tone::AVATAR[$i]" />
                    @endforeach
                    <x-ui.avatar name="Al-Barakah Livestock" shape="rounded" :size="44" />
                </div>
            </x-ui.card>
        </div>

        {{-- Filter bar + bulk bar + data table (stacked cards on phones) --}}
        <div x-data="{ selected: [], service: '', status: '', q: '' }">
            <h2 class="mb-3 text-[16px] font-bold text-ink">Penapis, Bar Pukal & Jadual</h2>
            <x-ui.filter-bar reset-action="service = ''; status = ''; q = ''" :has-filters="true" :active-count="0">
                <x-slot:tabs>
                    <div x-data="{ period: 'Harian' }"><x-ui.tabs alpine="period" variant="pill" :items="collect(['Harian', 'Mingguan', 'Bulanan', 'Tahunan', 'Custom'])->map(fn ($l) => ['key' => $l, 'label' => $l])->all()" /></div>
                </x-slot:tabs>
                <x-slot:search>
                    <x-ui.search-input placeholder="Cari nama, telefon, no. tempahan" x-model="q" />
                </x-slot:search>
                <x-slot:filters>
                    <x-ui.filter-select label="Servis" :options="['Qurban' => 'Qurban', 'Aqiqah' => 'Aqiqah', 'Dam' => 'Dam', 'Nazar Haiwan' => 'Nazar Haiwan']" x-model="service" />
                    <x-ui.filter-select label="Haiwan" :options="['Lembu' => 'Lembu', 'Kambing' => 'Kambing', 'Unta' => 'Unta']" />
                    <x-ui.filter-select label="Negara" :options="['Malaysia' => 'Malaysia', 'Uganda' => 'Uganda', 'Arab Saudi' => 'Arab Saudi']" />
                    <x-ui.filter-select label="Status" :options="['Draf' => 'Draf', 'Selesai' => 'Selesai']" x-model="status" />
                </x-slot:filters>
            </x-ui.filter-bar>

            <template x-if="selected.length > 0">
                <x-ui.bulk-bar clear-action="selected = []">
                    <x-slot:count><span x-text="selected.length"></span></x-slot:count>
                    <x-ui.button size="sm" variant="on-dark" icon="download-simple">Eksport</x-ui.button>
                    <x-ui.button size="sm" variant="gold" icon="users-three">Jana Senarai Peserta</x-ui.button>
                    <x-ui.button size="sm" variant="on-dark" icon="truck">Waybill</x-ui.button>
                    <x-ui.button size="sm" variant="gold" icon="certificate">Generate Sijil</x-ui.button>
                    <x-ui.button size="sm" variant="success" icon="fill check-circle">Diterima</x-ui.button>
                </x-ui.bulk-bar>
            </template>

            <x-ui.data-table :cols="$cols" min-width="1340px">
                <x-slot:head>
                    <span></span><span>No. Tempahan</span><span>Pelanggan</span><span>Servis</span><span>Haiwan</span><span>Negara</span><span class="text-center">Kuantiti</span><span class="text-right">Harga</span><span>Bayaran</span><span class="text-center">Status</span><span class="text-right">Tindakan</span>
                </x-slot:head>
                @foreach ($orders as $o)
                    <x-ui.tr>
                        <x-ui.td class="max-md:order-first max-md:col-span-2 max-md:flex max-md:items-center max-md:gap-3">
                            <x-ui.checkbox :size="18" value="{{ $o[0] }}" x-model="selected" aria-label="Pilih {{ $o[0] }}" />
                            <div class="md:hidden">
                                <div class="text-[13px] font-bold text-primary">{{ $o[0] }}</div>
                            </div>
                            <div class="ml-auto md:hidden"><x-ui.status-badge :status="$o[11]" /></div>
                        </x-ui.td>
                        <x-ui.td mobile="hide">
                            <div class="text-[12.5px] font-bold text-primary">{{ $o[0] }}</div>
                            @if ($o[12])<x-ui.badge tone="purple" variant="label" icon="calendar-check" class="mt-1">ANSURAN</x-ui.badge>@endif
                        </x-ui.td>
                        <x-ui.td span>
                            <div class="text-[13px] font-semibold text-ink">{{ $o[1] }}</div>
                            <div class="mt-0.5 text-[11.5px] text-muted">{{ $o[2] }}</div>
                        </x-ui.td>
                        <x-ui.td label="Servis" class="text-ink-2">{{ $o[3] }}</x-ui.td>
                        <x-ui.td label="Haiwan" class="text-ink-2">{{ $o[4] }}</x-ui.td>
                        <x-ui.td label="Negara" class="text-ink-2">{{ $o[6] }}</x-ui.td>
                        <x-ui.td label="Kuantiti" align="center" class="text-ink-2">{{ $o[7] }}</x-ui.td>
                        <x-ui.td label="Harga" align="right" class="font-semibold text-ink tabular-nums">{{ rm($o[8]) }}</x-ui.td>
                        <x-ui.td label="Bayaran"><x-ui.badge :tone="$o[10]" variant="tag">{{ $o[9] }}</x-ui.badge></x-ui.td>
                        <x-ui.td align="center" mobile="hide"><x-ui.status-badge :status="$o[11]" /></x-ui.td>
                        <x-ui.td align="right" span class="flex gap-1.5 md:justify-end">
                            <x-ui.button variant="gold-outline" icon-only size="sm" icon="receipt" aria-label="Lihat resit" />
                            <x-ui.button variant="secondary" icon-only size="sm" icon="eye" aria-label="Lihat" class="text-primary" />
                            <x-ui.button variant="secondary" icon-only size="sm" icon="pencil-simple" aria-label="Edit" class="text-muted" />
                        </x-ui.td>
                    </x-ui.tr>
                @endforeach
                <x-slot:footer>
                    <x-ui.pagination noun="tempahan" />
                </x-slot:footer>
            </x-ui.data-table>
        </div>

        {{-- Wide table: horizontal scroll + sticky first column --}}
        <div>
            <h2 class="mb-3 text-[16px] font-bold text-ink">Jadual Lebar (skrol mendatar + lajur pertama sticky)</h2>
            <x-ui.data-table cols="170px 1.4fr 1.6fr 0.8fr 1fr 1fr 1fr 1.1fr" min-width="1100px" scroll>
                <x-slot:head>
                    <span>No. Tempahan</span><span>Pelanggan</span><span>Alamat</span><span>Poskod</span><span>Bandar</span><span>Negeri</span><span>Kurier</span><span>No. Konsainan</span>
                </x-slot:head>
                @foreach (array_slice($orders, 0, 3) as $o)
                    <x-ui.tr scroll>
                        <x-ui.td class="font-bold text-primary">{{ $o[0] }}</x-ui.td>
                        <x-ui.td class="text-ink">{{ $o[1] }}</x-ui.td>
                        <x-ui.td class="text-ink-2">No. 12, Jln Melati 3</x-ui.td>
                        <x-ui.td class="text-ink-2">40150</x-ui.td>
                        <x-ui.td class="text-ink-2">Shah Alam</x-ui.td>
                        <x-ui.td class="text-ink-2">Selangor</x-ui.td>
                        <x-ui.td class="text-ink-2">Pos Laju</x-ui.td>
                        <x-ui.td class="font-semibold text-ink">EP{{ 482910 + $loop->index }}MY</x-ui.td>
                    </x-ui.tr>
                @endforeach
            </x-ui.data-table>
        </div>

        {{-- Stepper, empty state, form fields --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <x-ui.card title="Aliran Kerja" subtitle="Status pelaksanaan tempahan">
                <x-ui.stepper :steps="$steps" />
            </x-ui.card>
            <x-ui.card title="Keadaan Kosong" class="self-start">
                <x-ui.empty-state title="Tiada tempahan menunggu">
                    Hanya tempahan berstatus <b class="text-success">Diterima</b> akan muncul di sini.
                </x-ui.empty-state>
            </x-ui.card>
            <x-ui.card title="Medan Borang" class="self-start">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-ui.field label="Nama Penuh" placeholder="Nama pengguna" span />
                    <x-ui.field label="Emel" type="email" placeholder="emel@nadiqurban.com" />
                    <x-ui.field label="No. Telefon" placeholder="01X-XXXXXXX" />
                    <x-ui.field label="Nota" hint="(pilihan)" as="textarea" span placeholder="Catatan tambahan" />
                </div>
            </x-ui.card>
        </div>

        {{-- Modal + drawer triggers --}}
        <x-ui.card title="Modal & Drawer" subtitle="Desktop: kad di tengah · Telefon: skrin penuh dengan footer sticky">
            <div class="flex flex-wrap gap-3">
                <x-ui.button x-data @click="$dispatch('open-modal', 'demo')" icon="user-plus">Buka Modal</x-ui.button>
                <x-ui.button variant="secondary" x-data @click="$dispatch('open-drawer', 'log')" icon="sidebar-simple">Buka Drawer</x-ui.button>
            </div>
        </x-ui.card>

        {{-- Documents --}}
        <div class="grid gap-4 lg:grid-cols-2">
            <x-ui.card title="Pratonton A4" subtitle="Skala-muat pada skrin kecil">
                <div class="rounded-[10px] bg-bg p-3 md:p-5">
                    <x-ui.doc-a4>
                        <div class="flex items-start justify-between">
                            <img src="{{ asset('images/logo-512.png') }}" alt="" class="h-16">
                            <div class="text-right">
                                <div class="text-[22px] font-extrabold text-primary">RESIT TEMPAHAN</div>
                                <div class="mt-1 text-[12.5px] text-muted">NQ-QB-LE-001248 · {{ tarikh('2027-06-12') }}</div>
                            </div>
                        </div>
                        <div class="mt-10 border-t border-border pt-6 text-[13px] text-ink-2">Kandungan dokumen A4…</div>
                    </x-ui.doc-a4>
                </div>
            </x-ui.card>
            <x-ui.card title="Pratonton A5" subtitle="Sijil (148×210mm)">
                <div class="rounded-[10px] bg-bg p-3 md:p-5">
                    <x-ui.doc-a5>
                        <div class="absolute inset-3 border-[6px] border-double border-primary"></div>
                        <div class="relative flex h-full flex-col items-center px-12 pt-14 text-center">
                            <img src="{{ asset('images/logo-cert-128.png') }}" alt="" class="h-20">
                            <div class="mt-6 text-[24px] font-extrabold tracking-[1px] text-primary">SIJIL QURBAN</div>
                            <div class="font-arabic mt-3 text-[26px] text-gold">بِسْمِ ٱللَّٰهِ</div>
                        </div>
                    </x-ui.doc-a5>
                </div>
            </x-ui.card>
        </div>
    </div>

    <x-ui.modal name="demo" title="Tambah Pengguna" subtitle="Cipta akaun pengguna baharu" icon="user-plus">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-ui.field label="Nama Penuh" placeholder="Nama pengguna" span />
            <x-ui.field label="Emel" type="email" placeholder="emel@nadiqurban.com" />
            <x-ui.field label="No. Telefon" placeholder="01X-XXXXXXX" />
            <x-ui.field label="Kata Laluan" placeholder="Cipta kata laluan" />
            <x-ui.field label="Sahkan Kata Laluan" placeholder="Ulang kata laluan" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'demo')">Batal</x-ui.button>
            <x-ui.button icon="fill check-circle">Simpan Pengguna</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.drawer name="log" title="Butiran Log" subtitle="LOG-20482">
        <dl class="grid grid-cols-[110px_1fr] gap-y-3 text-[13px]">
            <dt class="text-faint">Pengguna</dt><dd class="font-semibold text-ink">Fatimah (Kewangan)</dd>
            <dt class="text-faint">Tindakan</dt><dd class="text-ink-2">Bayaran disahkan</dd>
            <dt class="text-faint">IP</dt><dd class="text-ink-2">203.106.12.44</dd>
            <dt class="text-faint">Masa</dt><dd class="text-ink-2">{{ tarikh(now(), true) }}</dd>
        </dl>
    </x-ui.drawer>
</x-layouts::app>
