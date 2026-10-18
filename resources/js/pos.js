/**
 * Punto de venta (Alpine). Pensado para alguien que nunca usó un sistema:
 *  - Un solo buscador: escribe el nombre o pasa la pistola de código de barras y presiona Enter.
 *  - Tocar un producto lo agrega; + y − cambian la cantidad.
 *  - "Cobrar" abre un paso a paso: cómo paga → (vuelto o cliente) → comprobante → listo.
 *
 * El catálogo viene completo en la página (búsqueda instantánea). El servidor vuelve a validar
 * precios y stock al cobrar, así que lo de aquí es solo para mostrar.
 */
const normalize = (text) => (text ?? '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
const round2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;
const moneyFormat = new Intl.NumberFormat('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default (config) => ({
    products: config.products.map((p) => ({ ...p, _search: normalize(p.name) })),
    categories: config.categories,
    vouchers: config.vouchers,
    urls: config.urls,
    methods: config.methods,

    search: '',
    category: null,
    cart: [],
    notice: '',
    noticeTimer: null,

    // Cobro
    checkout: false,
    step: 'method',            // method | details | done
    method: null,
    received: '',
    voucher: 'ticket',
    customer: { id: null, name: '', document: '', address: '', phone: '', debt: 0 },
    clientQuery: '',
    clientResults: [],
    newClient: false,
    sending: false,
    error: '',
    result: null,

    init() {
        this.$nextTick(() => this.focusSearch());
    },

    money(value) {
        return 'S/ ' + moneyFormat.format(Number(value) || 0);
    },

    qtyLabel(value) {
        return Number(value).toLocaleString('es-PE', { maximumFractionDigits: 3 });
    },

    focusSearch() {
        this.$refs.search?.focus();
    },

    flash(message) {
        this.notice = message;
        clearTimeout(this.noticeTimer);
        this.noticeTimer = setTimeout(() => (this.notice = ''), 3500);
    },

    // ── Catálogo ─────────────────────────────────────────────
    get results() {
        const term = normalize(this.search.trim());
        let list = this.products;

        if (term) {
            const words = term.split(/\s+/);
            list = list.filter((p) => p.barcode === this.search.trim() || words.every((w) => p._search.includes(w)));
        } else if (this.category) {
            list = list.filter((p) => p.category === this.category);
        }

        return list.slice(0, 60);
    },

    /** Enter en el buscador: código de barras exacto o un único resultado → se agrega */
    submitSearch() {
        const term = this.search.trim();
        if (!term) return;

        const byBarcode = this.products.find((p) => p.barcode && p.barcode === term);
        const target = byBarcode ?? (this.results.length === 1 ? this.results[0] : null);

        if (target) {
            this.add(target);
            this.search = '';
        } else if (this.results.length === 0) {
            this.flash(`No encontré «${term}». Revisa cómo está escrito.`);
            this.search = '';
        } else {
            this.flash('Hay varios productos con ese nombre: toca el que quieres.');
        }
    },

    // ── Carrito ──────────────────────────────────────────────
    add(product) {
        const line = this.cart.find((l) => l.id === product.id);
        const next = (line ? line.qty : 0) + 1;

        if (product.stock <= 0) {
            this.flash(`«${product.name}» no tiene stock.`);
            return;
        }
        if (next > product.stock && !product.fractional) {
            this.flash(`Solo quedan ${this.qtyLabel(product.stock)} de «${product.name}».`);
            return;
        }

        if (line) {
            line.qty = product.fractional ? line.qty : next;
            // Los productos por kilo se agregan una vez y se escribe el peso
            if (product.fractional) this.flash(`Escribe cuántos ${product.unit.toLowerCase()}s de «${product.name}».`);
        } else {
            this.cart.unshift({ ...product, qty: 1 });
            if (product.fractional) this.flash(`Escribe cuántos ${product.unit.toLowerCase()}s de «${product.name}».`);
        }
        this.focusSearch();
    },

    increase(line) {
        if (line.qty + 1 > line.stock) {
            this.flash(`Solo quedan ${this.qtyLabel(line.stock)} de «${line.name}».`);
            return;
        }
        line.qty = round2(line.qty + 1);
    },

    decrease(line) {
        if (line.qty - 1 <= 0) {
            this.remove(line);
            return;
        }
        line.qty = round2(line.qty - 1);
    },

    setQty(line, value) {
        const qty = Number(String(value).replace(',', '.'));
        if (!qty || qty <= 0) return;
        line.qty = line.fractional ? Math.round(qty * 1000) / 1000 : Math.floor(qty) || 1;
        if (line.qty > line.stock) {
            this.flash(`Solo quedan ${this.qtyLabel(line.stock)} de «${line.name}».`);
            line.qty = line.stock;
        }
    },

    remove(line) {
        this.cart = this.cart.filter((l) => l !== line);
        this.focusSearch();
    },

    clearCart() {
        this.cart = [];
        this.focusSearch();
    },

    lineTotal(line) {
        return round2(line.price * line.qty);
    },

    get total() {
        return round2(this.cart.reduce((sum, line) => sum + this.lineTotal(line), 0));
    },

    get itemsCount() {
        return this.cart.reduce((sum, line) => sum + (line.fractional ? 1 : line.qty), 0);
    },

    // ── Cobro ────────────────────────────────────────────────
    openCheckout() {
        if (!this.cart.length) {
            this.flash('Primero agrega productos a la venta.');
            return;
        }
        this.checkout = true;
        this.step = 'method';
        this.method = null;
        this.received = '';
        this.voucher = 'ticket';
        this.error = '';
        this.resetCustomer();
    },

    closeCheckout() {
        if (this.sending) return;
        this.checkout = false;
        this.focusSearch();
    },

    chooseMethod(method) {
        this.method = method;
        this.error = '';
        this.step = 'details';
        this.$nextTick(() => {
            if (method === 'efectivo') this.$refs.received?.focus();
            if (method === 'fiado') this.$refs.clientQuery?.focus();
        });
    },

    /** Billetes sugeridos para pagar: exacto y los billetes que alcanzan */
    get bills() {
        const total = this.total;
        const options = [10, 20, 50, 100, 200].filter((b) => b > total);
        return options.slice(0, 3);
    },

    get change() {
        const received = Number(this.received || 0);
        return received >= this.total ? round2(received - this.total) : null;
    },

    get missing() {
        const received = Number(this.received || 0);
        return this.received !== '' && received < this.total ? round2(this.total - received) : 0;
    },

    resetCustomer() {
        this.customer = { id: null, name: '', document: '', address: '', phone: '', debt: 0 };
        this.clientQuery = '';
        this.clientResults = [];
        this.newClient = false;
    },

    async searchClients() {
        const q = this.clientQuery.trim();
        if (q.length < 2) {
            this.clientResults = [];
            return;
        }
        const { data } = await window.axios.get(this.urls.clients, { params: { q } });
        this.clientResults = data;
    },

    pickClient(client) {
        this.customer = { ...client };
        this.clientResults = [];
        this.clientQuery = '';
        this.newClient = false;
    },

    get needsDocument() {
        return this.voucher === 'factura';
    },

    /** ¿Se puede confirmar? (evita errores obvios antes de enviar) */
    get canConfirm() {
        if (!this.method) return false;
        if (this.method === 'efectivo' && this.missing > 0) return false;
        if (this.method === 'fiado' && !this.customer.id && !this.customer.name.trim()) return false;
        if (this.voucher === 'factura' && (this.customer.document.replace(/\D/g, '').length !== 11 || !this.customer.name.trim())) return false;
        return true;
    },

    async confirm() {
        if (!this.canConfirm || this.sending) return;
        this.sending = true;
        this.error = '';

        try {
            const { data } = await window.axios.post(this.urls.store, {
                items: this.cart.map((l) => ({ product_id: l.id, quantity: l.qty })),
                voucher_type: this.voucher,
                payment_method: this.method,
                amount_received: this.method === 'efectivo' && this.received !== '' ? this.received : null,
                client_id: this.customer.id,
                customer_name: this.customer.name || null,
                customer_document: this.customer.document || null,
                customer_address: this.customer.address || null,
                customer_phone: this.customer.phone || null,
            });

            // Stock actualizado de lo vendido
            Object.entries(data.stock).forEach(([id, stock]) => {
                const product = this.products.find((p) => p.id === Number(id));
                if (product) product.stock = stock;
            });

            this.result = data;
            this.step = 'done';
            this.cart = [];
        } catch (e) {
            const errors = e.response?.data?.errors;
            this.error = errors ? Object.values(errors).flat()[0] : (e.response?.data?.message ?? 'No se pudo registrar la venta. Revisa tu conexión e intenta otra vez.');
        } finally {
            this.sending = false;
        }
    },

    printTicket() {
        if (this.result) window.open(this.result.ticket_url, '_blank');
    },

    newSale() {
        this.checkout = false;
        this.result = null;
        this.focusSearch();
    },
});
