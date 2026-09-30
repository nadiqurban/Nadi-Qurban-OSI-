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
