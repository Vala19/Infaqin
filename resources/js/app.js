import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

const formatRupiah = (value) => 'Rp' + Number(value || 0).toLocaleString('id-ID');

Alpine.data('carousel', (count = 0, interval = 6000) => ({
    active: 0,
    count,
    timer: null,
    init() {
        this.play();
    },
    play() {
        if (this.count < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        this.stop();
        this.timer = setInterval(() => this.next(), interval);
    },
    stop() {
        clearInterval(this.timer);
    },
    go(index) {
        this.active = (index + this.count) % this.count;
        this.play();
    },
    next() {
        this.go(this.active + 1);
    },
    prev() {
        this.go(this.active - 1);
    },
}));

Alpine.data('zakatCalculator', () => ({
    savings: '',
    gold: '',
    investments: '',
    debts: '',
    goldPrice: 1750000,
    get wealth() {
        const n = (v) => Number(String(v).replace(/\D/g, '')) || 0;
        return Math.max(0, n(this.savings) + n(this.gold) + n(this.investments) - n(this.debts));
    },
    get nisab() {
        return 85 * (Number(this.goldPrice) || 0);
    },
    get eligible() {
        return this.wealth >= this.nisab && this.nisab > 0;
    },
    get zakat() {
        return this.eligible ? Math.ceil(this.wealth * 0.025) : 0;
    },
    rupiah: formatRupiah,
}));

Alpine.data('amenButton', (url, amened, count) => ({
    amened,
    count,
    busy: false,
    async toggle() {
        if (this.busy) return;
        this.busy = true;
        try {
            const { data } = await window.axios.post(url);
            this.amened = data.amened;
            this.count = data.count;
        } catch (error) {
            if (error.response?.status === 401) window.location.href = '/login';
        } finally {
            this.busy = false;
        }
    },
}));

Alpine.data('shareButton', (url, title) => ({
    copied: false,
    open: false,
    url,
    title,
    async share() {
        if (navigator.share) {
            try {
                await navigator.share({ title, url });
                return;
            } catch (e) {
                if (e.name === 'AbortError') return;
            }
        }
        this.open = !this.open;
    },
    async copy() {
        try {
            await navigator.clipboard.writeText(url);
        } catch (e) {
            const input = document.createElement('input');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            input.remove();
        }
        this.copied = true;
        setTimeout(() => (this.copied = false), 2000);
    },
}));

Alpine.data('loadMore', (nextUrl) => ({
    nextUrl,
    loading: false,
    async load() {
        if (!this.nextUrl || this.loading) return;
        this.loading = true;
        try {
            const { data } = await window.axios.get(this.nextUrl, { headers: { Accept: 'text/html' } });
            const template = document.createElement('template');
            template.innerHTML = data;
            const next = template.content.querySelector('[data-next-url]');
            this.nextUrl = next?.dataset.nextUrl || null;
            next?.remove();
            this.$refs.list.append(...template.content.childNodes);
        } finally {
            this.loading = false;
        }
    },
}));

// Opens Snap and, once it closes, polls the read-only status endpoint. The status is only ever
// changed server-side by the Midtrans webhook, so the browser just waits and reloads.
Alpine.data('snapPayment', (token, statusUrl, autoOpen = false) => ({
    polling: false,
    timedOut: false,
    timer: null,
    init() {
        if (autoOpen) this.$nextTick(() => this.pay());
    },
    pay() {
        if (!window.snap) return;
        const done = () => this.watch();
        window.snap.pay(token, { onSuccess: done, onPending: done, onError: done, onClose: done });
    },
    watch() {
        clearInterval(this.timer);
        this.polling = true;
        this.timedOut = false;
        const startedAt = Date.now();
        this.timer = setInterval(async () => {
            if (Date.now() - startedAt >= 120_000) {
                clearInterval(this.timer);
                this.polling = false;
                this.timedOut = true;
                return;
            }
            try {
                const { data } = await window.axios.get(statusUrl);
                if (data.status !== 'pending') window.location.reload();
            } catch {
                // Keep polling; the button below lets the donor refresh manually.
            }
        }, 5000);
    },
    destroy() {
        clearInterval(this.timer);
    },
}));

window.formatRupiah = formatRupiah;
window.Alpine = Alpine;
Alpine.start();
