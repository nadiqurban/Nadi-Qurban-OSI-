@php
    $steps = \App\Livewire\Public\Tracking::STEPS;
    $phone = $settings->get('support.phone', $settings->get('company.phone'));
    $whatsapp = preg_replace('/\D+/', '', (string) $settings->get('support.whatsapp', $phone));
@endphp

<div>
    <div class="mb-[26px] text-center">
        <h1 class="text-[22px] font-extrabold text-ink md:text-[26px]">Jejak Status Tempahan Anda</h1>
        <p class="mx-auto mt-2 max-w-[460px] text-[14px] leading-[1.6] text-muted">Masukkan nombor tracking yang diberikan untuk melihat perkembangan ibadah anda secara langsung.</p>
    </div>

    <form wire:submit="search" class="mb-6 flex flex-wrap gap-[10px] rounded-[12px] border border-border bg-surface p-[10px]">
        <label class="flex min-w-[220px] flex-1 items-center gap-[10px] rounded-[9px] border border-border bg-bg px-[15px] py-3">
            <i class="ph ph-magnifying-glass text-[18px] text-faint"></i>
            <input type="text" wire:model="input" placeholder="cth. NQ-QB-LE-001248" aria-label="No. tracking atau no. tempahan" autocomplete="off"
                   class="flex-1 bg-transparent text-[16px] text-ink tabular-nums outline-none md:text-[14px]">
        </label>
        <x-ui.button type="submit" icon="magnifying-glass" class="!px-6 !py-3 !text-[14px] !font-bold max-md:w-full">Semak</x-ui.button>
    </form>

    @if ($throttled)
        <div class="rounded-[12px] border border-border bg-surface px-6 py-12 text-center">
            <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-[14px] bg-warning-soft text-warning"><i class="ph ph-hourglass text-[28px]"></i></div>
            <div class="text-[16px] font-bold text-ink">Terlalu banyak carian</div>
            <p class="mx-auto mt-1.5 max-w-[360px] text-[13px] leading-[1.6] text-muted">Sila cuba semula selepas seminit.</p>
        </div>
    @elseif ($order)
        <div class="flex flex-col gap-5">
            {{-- Status summary --}}
            <div class="rounded-[14px] bg-primary px-5 py-6 text-white md:px-[26px]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="text-[11.5px] font-semibold tracking-[1px] text-gold">NO. TRACKING</div>
                        <div class="mt-1 text-[20px] font-extrabold tabular-nums">{{ $order->order_no }}</div>
                        <div class="mt-1.5 text-[13px] text-[#cfe0d7]">{{ $order->service->label() }} · {{ $order->animal->label() }} ({{ $order->quantity }} bahagian)</div>
                    </div>
                    <span class="rounded-[20px] bg-gold-soft px-[13px] py-[5px] text-[12px] font-bold text-gold-ink">{{ $order->stage->customerLabel() }}</span>
                </div>
                <div class="my-[18px] h-px bg-white/15"></div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-5">
                    @foreach (['Peserta Utama' => $mainName, 'Negara Pelaksanaan' => $order->country->name, 'Pakej' => $order->package_name] as $k => $v)
                        <div><div class="text-[11px] tracking-[.3px] text-[#9fbaad]">{{ $k }}</div><div class="mt-1 text-[14px] leading-[1.4] font-bold">{{ $v }}</div></div>
                    @endforeach
                </div>
            </div>

            {{-- Participants --}}
            @if ($order->quantity > 1)
                <section class="rounded-[12px] border border-border bg-surface px-5 py-6 md:px-[26px]">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-[16px] font-bold text-ink">Senarai Peserta</h2>
                        <span class="rounded-[20px] bg-primary px-3 py-1 text-[12.5px] font-extrabold text-white">{{ $order->quantity }} bahagian</span>
                    </div>
                    <div class="grid grid-cols-[repeat(auto-fill,minmax(220px,1fr))] gap-[10px]">
                        @foreach ($names as $i => $n)
                            <div class="flex items-center gap-[10px] rounded-[9px] border border-border bg-bg px-3 py-[10px]">
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-[7px] bg-primary text-[11px] font-bold text-white">{{ $i + 1 }}</span>
                                <span class="text-[13px] font-semibold text-ink">{{ $n }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Progress --}}
            <section class="rounded-[12px] border border-border bg-surface px-5 py-6 md:px-[26px]">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-[16px] font-bold text-ink">Perkembangan Ibadah</h2>
                    <span class="rounded-[20px] bg-success-soft px-3 py-1 text-[12.5px] font-bold text-success">{{ $pct }}% selesai</span>
                </div>
                <div class="flex flex-col">
                    @foreach ($steps as $i => [$title, $desc])
                        @php $isDone = $i < $done; $isCur = $i === $done; @endphp
                        <div class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <span @class(['flex size-[38px] shrink-0 items-center justify-center rounded-full', 'bg-primary text-white' => $isDone, 'bg-gold text-white' => $isCur, 'bg-divider text-faint' => ! $isDone && ! $isCur])>
                                    <i class="{{ $isDone ? 'ph-fill ph-check' : ($isCur ? 'ph-fill ph-circle-notch' : 'ph ph-circle') }} text-[17px]"></i>
                                </span>
                                <span @class(['my-1 min-h-[14px] w-0.5 flex-1', 'bg-primary' => $isDone && ! $loop->last, 'bg-border' => ! $isDone && ! $loop->last])></span>
                            </div>
                            <div class="flex-1 pb-[22px]">
                                <div @class(['text-[14px] font-bold', 'text-ink' => $isDone, 'text-gold-ink' => $isCur, 'text-faint' => ! $isDone && ! $isCur])>{{ $title }}</div>
                                <div class="mt-[3px] text-[12.5px] leading-normal text-muted">{{ $desc }}</div>
                                @if ($isDone && $times[$i])
                                    <div class="mt-1 flex items-center gap-[5px] text-[11.5px] text-faint"><i class="ph ph-clock text-[13px]"></i>{{ tarikh($times[$i], true) }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Jom Lafaz Akad --}}
            <div class="rounded-[14px] bg-primary px-5 py-6 text-white md:px-[26px]">
                <div class="mb-[14px] inline-flex items-center gap-2 rounded-[20px] border border-[rgba(201,162,39,.4)] bg-[rgba(201,162,39,.2)] px-[14px] py-[5px]"><span class="text-[12.5px] font-bold tracking-[.3px] text-[#e7cf88]">Alhamdulillah | Jom Lafaz Akad</span></div>
                <p class="text-[13.5px] leading-[1.6] text-[#e6e8dc]">Tuan/Puan boleh terus lafaz akad sekarang. Sila baca sahaja lafaz akad:</p>
                <div class="mt-[14px] rounded-[11px] border border-white/15 bg-white/10 px-[22px] py-5">
                    <p dir="rtl" lang="ar" class="font-arabic text-center text-[20px] leading-[2] text-white md:text-[24px]">سَايَا مِوَكِيلْكَنْ شَرِيكَتْ نَادِي قُرْبَانْ بَرْتَنْݢُوڠْجَوَابْ أَتَسْ ڤَلَكْسَانَاءَنْ عِبَادَةِ (قُرْبَانْ/عَقِيقَةْ/نَذَرْ) ڤَدَا تَاهُونْ إِينِي دِي أَتَسْ نَامَا يَڠ دِدَفْتَرْكَنْ كَرَانَ اللَّهِ تَعَالَى</p>
                    <div class="my-4 h-px bg-white/15"></div>
                    <p class="text-center text-[13.5px] leading-[1.7] text-[#e6e8dc] italic">"Saya mewakilkan Syarikat Nadi Qurban bertanggungjawab atas pelaksanaan ibadah (Qurban/Aqiqah/Nazar) pada tahun ini di atas nama yang didaftarkan kerana Allah Ta'ala."</p>
                </div>
            </div>

            {{-- Nota penting --}}
            <div class="rounded-[14px] border border-[#E7D9A8] bg-[#FEF9F0] px-5 py-[22px] md:px-6">
                <div class="mb-[14px] flex flex-wrap items-center gap-x-[9px] gap-y-1"><span class="text-[14px] font-extrabold text-[#8a6d1a]">NOTA PENTING</span><span class="text-[12px] text-[#a8851a]">(Bagi Peserta yang Mendaftar Ibadah Qurban Sahaja)</span></div>
                <div class="flex flex-col gap-[14px]">
                    <div><div class="text-[13.5px] font-bold text-primary">1. Sijil &amp; Hadiah</div><div class="mt-[3px] text-[13px] leading-[1.6] text-muted">Sijil penyertaan dan hadiah akan diposkan selepas selesai pelaksanaan ibadah Qurban dalam tempoh 60 hari bekerja.</div></div>
                    <div><div class="text-[13.5px] font-bold text-primary">2. Bukti Laporan (Video &amp; Gambar)</div><div class="mt-[3px] text-[13px] leading-[1.6] text-muted">Video dan gambar pelaksanaan akan dikemaskini secara berperingkat mengikut negara pelaksanaan ibadah Qurban yang dipilih. Bagi mendapatkan pautan akses laporan akhir, sila rujuk semula pegawai yang telah menguruskan pendaftaran Tuan/Puan.</div></div>
                </div>
                <div class="mt-[14px] border-t border-[#E7D9A8] pt-[14px] text-[12.5px] text-[#8a6d1a] italic">Terima kasih atas kesabaran dan kerjasama yang diberikan.</div>
            </div>
        </div>
    @elseif ($query !== '')
        <div class="rounded-[12px] border border-border bg-surface px-6 py-12 text-center">
            <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-[14px] bg-danger-soft text-danger"><i class="ph ph-magnifying-glass-minus text-[28px]"></i></div>
            <div class="text-[16px] font-bold text-ink">Tiada rekod dijumpai</div>
            <p class="mx-auto mt-1.5 max-w-[360px] text-[13px] leading-[1.6] text-muted">Sila semak semula nombor tracking anda. Contoh format: <b class="text-primary">NQ-QB-LE-001248</b></p>
        </div>
    @endif

    {{-- Help --}}
    <div id="bantuan" @class(['flex flex-wrap items-center gap-4 rounded-[12px] border border-border bg-surface px-5 py-[22px] md:flex-nowrap md:px-6', 'mt-5' => true])>
        <div class="flex size-[46px] shrink-0 items-center justify-center rounded-[12px] bg-primary-soft text-primary"><i class="ph ph-headset text-[22px]"></i></div>
        <div class="min-w-0 flex-1"><div class="text-[14px] font-bold text-ink">Ada pertanyaan tentang tempahan?</div><div class="mt-0.5 text-[12.5px] text-muted">Hubungi Khidmat Pelanggan Nadi Qurban.</div></div>
        <div class="flex shrink-0 flex-wrap items-center gap-[10px] max-md:w-full">
            <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-[7px] rounded-[9px] bg-success-soft px-4 py-[10px] text-[13px] font-semibold whitespace-nowrap text-success hover:text-success max-md:flex-1 max-md:justify-center"><i class="ph-fill ph-whatsapp-logo text-[17px]"></i> WhatsApp</a>
            <a href="tel:+{{ preg_replace('/\D+/', '', (string) $phone) }}" class="inline-flex min-h-11 items-center gap-[7px] text-[13px] font-semibold whitespace-nowrap text-ink-2 max-md:flex-1 max-md:justify-center"><i class="ph ph-phone text-[16px] text-primary"></i> {{ $phone }}</a>
        </div>
    </div>
</div>
