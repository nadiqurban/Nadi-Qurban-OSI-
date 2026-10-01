import Sortable from 'sortablejs';
import { geoMercator, geoPath, select } from 'd3';
import { feature } from 'topojson-client';

// Alpine ships with Livewire 4 (injected automatically); register global stores/components here.

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Dashboard "Agihan Negara": d3 + topojson world map with scaled pins
    // (Dashboard Operasi.dc.html _renderMap). Redraws on resize and theme change.
    Alpine.data('countryMap', (points, height = 200) => ({
        topo: null,
        ro: null,
        onTheme: null,
        async init() {
            const mod = await import('world-atlas/countries-110m.json');
            this.topo = mod.default ?? mod;
            this.draw();
            // Redraw only when the width really changes, on the next frame (avoids ResizeObserver loops).
            let lastWidth = this.$el.clientWidth;
            let frame = null;
            this.ro = new ResizeObserver(() => {
                const width = this.$el.clientWidth;
                if (Math.abs(width - lastWidth) < 1) return;
                lastWidth = width;
                cancelAnimationFrame(frame);
                frame = requestAnimationFrame(() => this.draw());
            });
            this.ro.observe(this.$el);
            this.onTheme = () => this.draw();
            window.addEventListener('nq-theme', this.onTheme);
        },
        destroy() {
            this.ro?.disconnect();
            window.removeEventListener('nq-theme', this.onTheme);
        },
        draw() {
            if (!this.topo) return;
            const el = this.$el;
            const dark = document.documentElement.dataset.theme === 'dark';
            const W = el.clientWidth || 340;
            const H = window.innerWidth < 768 ? 180 : height;
            el.innerHTML = '';
            const svg = select(el).append('svg').attr('viewBox', `0 0 ${W} ${H}`).attr('width', '100%').attr('height', H)
                .attr('role', 'img').attr('aria-label', 'Peta agihan negara').style('display', 'block');
            svg.append('rect').attr('width', W).attr('height', H).attr('rx', 10).attr('fill', dark ? 'rgba(90,120,160,.10)' : '#eef3f6');
            const proj = geoMercator().center([48, 14]).scale(W * 0.62).translate([W / 2, H / 2]);
            const path = geoPath(proj);
            svg.append('g').selectAll('path').data(feature(this.topo, this.topo.objects.countries).features).join('path')
                .attr('d', path)
                .attr('fill', dark ? 'rgba(201,162,39,.14)' : '#e2e5d0')
                .attr('stroke', dark ? 'rgba(201,162,39,.25)' : '#cfd3b6').attr('stroke-width', 0.5);
            const maxV = Math.max(1, ...points.map((d) => d.value));
            points.forEach((d) => {
                const p = proj([d.lon, d.lat]);
                if (!p) return;
                const rad = 5 + (d.value / maxV) * 11;
                const g = svg.append('g');
                g.append('title').text(`${d.name}: ${d.value}%`);
                g.append('circle').attr('cx', p[0]).attr('cy', p[1]).attr('r', rad + 5).attr('fill', d.color).attr('opacity', 0.16);
                g.append('circle').attr('cx', p[0]).attr('cy', p[1]).attr('r', rad).attr('fill', d.color)
                    .attr('stroke', dark ? '#14201B' : '#fff').attr('stroke-width', 2);
                g.append('text').attr('x', p[0]).attr('y', p[1] + 3.2).attr('text-anchor', 'middle')
                    .attr('font-size', 9).attr('font-weight', 700).attr('font-family', 'Inter,sans-serif')
                    .attr('fill', d.value > 15 || d.color === '#42481c' ? '#fff' : '#1A1D21').text(d.value);
            });
        },
    }));

    // Sales CRM Kanban column: drag cards between columns (SortableJS) and persist
    // stage + order through the Livewire component's moveLead(id, stage, orderedIds).
    Alpine.data('kanbanColumn', (stage) => ({
        init() {
            Sortable.create(this.$el, {
                group: 'crm-leads',
                animation: 150,
                draggable: '[data-lead]',
                ghostClass: 'opacity-40',
                delay: 150,
                delayOnTouchOnly: true,
                forceFallback: true,
                fallbackTolerance: 3,
                onEnd: (evt) => {
                    const to = evt.to;
                    const ids = [...to.querySelectorAll('[data-lead]')].map((n) => Number(n.dataset.lead));
                    const target = to.closest('[data-stage]')?.dataset.stage ?? stage;
                    // Put the node back so Livewire's morph (keyed cards) owns the DOM.
                    evt.item.remove();
                    evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex] ?? null);
                    this.$wire.moveLead(Number(evt.item.dataset.lead), target, ids);
                },
            });
        },
    }));

    // Global UI state: mobile sidebar drawer, search overlay, theme.
    Alpine.store('ui', {
        sidebar: false,
        search: false,
        theme: document.documentElement.dataset.theme || 'light',

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = this.theme;
            // Stored per user (Dashboard design: global light/dark toggle).
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch('/tetapan/tema', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token ?? '', Accept: 'application/json' },
                body: JSON.stringify({ theme: this.theme }),
            }).catch(() => {});
            window.dispatchEvent(new CustomEvent('nq-theme', { detail: this.theme }));
        },
    });

    // Password strength meter (Login.dc.html): score = length≥8, number, uppercase, symbol.
    Alpine.data('passwordStrength', () => ({
        pw: '',
        confirm: '',
        get hasLen() { return this.pw.length >= 8; },
        get hasNum() { return /[0-9]/.test(this.pw); },
        get hasUp() { return /[A-Z]/.test(this.pw); },
        get hasSym() { return /[^A-Za-z0-9]/.test(this.pw); },
        get score() { return [this.hasLen, this.hasNum, this.hasUp, this.hasSym].filter(Boolean).length; },
        get label() {
            if (!this.pw.length) return '—';
            return ['Lemah', 'Lemah', 'Sederhana', 'Baik', 'Kuat'][this.score];
        },
        get color() {
            if (!this.pw.length) return '#94A3AC';
            return ['#DC2626', '#DC2626', '#D97706', '#C9A227', '#16A34A'][this.score];
        },
        get rules() {
            return [
                { ok: this.hasLen, label: 'Sekurang-kurangnya 8 aksara' },
                { ok: this.hasUp && this.hasNum, label: 'Ada huruf besar & nombor' },
                { ok: this.hasSym, label: 'Ada simbol (cth. ! @ #)' },
            ];
        },
        get matches() { return this.pw.length > 0 && this.pw === this.confirm; },
        get mismatch() { return this.confirm.length > 0 && !this.matches; },
        get canSubmit() { return this.hasLen && this.hasUp && this.hasNum && this.matches; },
    }));

    // Logs the user out after `minutes` without interaction (PRD: 30 min idle).
    Alpine.data('idleLogout', (minutes) => ({
        timer: null,
        init() {
            const reset = () => {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.$refs.form.submit(), minutes * 60 * 1000);
            };
            ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach((e) =>
                window.addEventListener(e, reset, { passive: true }));
            reset();
        },
    }));

    // Payment-proof preview: images inline, PDFs render page 1 with pdf.js (loaded on demand).
    Alpine.data('proofPreview', (url, isPdf) => ({
        loading: isPdf,
        failed: false,
        async init() {
            if (!isPdf || !url) return;
            try {
                const pdfjs = await import('pdfjs-dist');
                const worker = await import('pdfjs-dist/build/pdf.worker.min.mjs?url');
                pdfjs.GlobalWorkerOptions.workerSrc = worker.default;
                const pdf = await pdfjs.getDocument({ url, withCredentials: true }).promise;
                const page = await pdf.getPage(1);
                const dpr = Math.max(2, window.devicePixelRatio || 1);
                const base = page.getViewport({ scale: 1 });
                const scale = (Math.min(this.$el.clientWidth || 480, 520) / base.width) * dpr;
                const viewport = page.getViewport({ scale });
                const canvas = this.$refs.canvas;
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                canvas.style.width = viewport.width / dpr + 'px';
                await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
            } catch (e) {
                this.failed = true;
            } finally {
                this.loading = false;
            }
        },
    }));

    // Scales an A4/A5 preview down to fit its container on small screens.
    Alpine.data('docScale', (pageWidthPx) => ({
        scale: 1,
        init() {
            // Only the container width matters; defer to the next frame so the
            // height change we cause does not re-enter the observer loop.
            let frame = null;
            const fit = () => {
                cancelAnimationFrame(frame);
                frame = requestAnimationFrame(() => {
                    const next = Math.min(1, this.$el.clientWidth / pageWidthPx);
                    if (Math.abs(next - this.scale) > 0.001) this.scale = next;
                });
            };
            fit();
            new ResizeObserver(fit).observe(this.$el);
        },
    }));
});

// ⌘K / Ctrl+K opens the global search overlay.
document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        if (window.Alpine) {
            window.Alpine.store('ui').search = true;
        }
    }
});

// Print one on-screen A4 preview (e.g. Kewangan draft invoice) in a hidden iframe,
// reusing the page's stylesheets so it prints exactly as previewed.
window.nqPrint = (el) => {
    if (!el) return window.print();
    const frame = document.createElement('iframe');
    frame.style.cssText = 'position:fixed; right:0; bottom:0; width:0; height:0; border:0;';
    document.body.appendChild(frame);
    const styles = [...document.querySelectorAll('link[rel="stylesheet"], style')].map((n) => n.outerHTML).join('');
    const doc = frame.contentDocument;
    doc.open();
    doc.write(`<!doctype html><html><head><meta charset="utf-8">${styles}<style>@page{size:A4;margin:0}body{margin:0;background:#fff}</style></head><body>${el.outerHTML}</body></html>`);
    doc.close();
    const page = doc.body.firstElementChild;
    if (page) { page.style.transform = 'none'; page.style.boxShadow = 'none'; }
    let done = false;
    const go = () => { if (done) return; done = true; frame.contentWindow.focus(); frame.contentWindow.print(); setTimeout(() => frame.remove(), 1000); };
    frame.onload = go;
    setTimeout(go, 400);
};
