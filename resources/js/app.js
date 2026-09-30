// Alpine ships with Livewire 4 (injected automatically); register global stores/components here.

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Global UI state: mobile sidebar drawer, search overlay, theme.
    Alpine.store('ui', {
        sidebar: false,
        search: false,
        theme: document.documentElement.dataset.theme || 'light',

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = this.theme;
            try {
                localStorage.setItem('nq_theme', this.theme);
            } catch (e) {}
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
