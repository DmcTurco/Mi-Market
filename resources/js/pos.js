/**
 * Punto de venta (Alpine). Pensado para alguien que nunca usó un sistema:
 *  - Un solo buscador: escribe el nombre o pasa la pistola de código de barras y presiona Enter.
 *  - Tocar un producto lo agrega. Si se vende de varias formas (unidad, media caja, caja) pregunta cuál;
 *    si se vende por peso (kilo, litro) pregunta cuánto marcó la balanza o por cuántos soles.
 *  - "Cobrar" abre un paso a paso: cómo paga → (vuelto o cliente) → comprobante → listo.
 *
 * El stock siempre se cuenta en la unidad base: 1 caja de 12 usa 12 del stock.
 * El catálogo viene completo en la página (búsqueda instantánea). El servidor vuelve a validar
 * precios y stock al cobrar, así que lo de aquí es solo para mostrar.
 */
const normalize = (text) => (text ?? '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
const round2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;
const round3 = (n) => Math.round((Number(n) + Number.EPSILON) * 1000) / 1000;
const toNumber = (value) => Number(String(value ?? '').replace(',', '.')) || 0;
const moneyFormat = new Intl.NumberFormat('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default (config) => ({
    products: config.products.map((p) => ({ ...p, presentations: p.presentations ?? [], _search: normalize(p.name) })),
    categories: config.categories,
    vouchers: config.vouchers,
    urls: config.urls,
    methods: config.methods,
    settings: config.settings ?? { allowDiscounts: false, maxDiscountPercent: 0, rounding: null },

    // Rebaja de una línea ("te lo dejo a S/ 6")
    priceLine: null,
    priceInput: '',

    // Descuento a toda la venta
    discountOpen: false,
    discountMode: 'amount',     // amount (S/) | percent (%)
    discountValue: '',
    discountDraft: '',

    search: '',
    category: null,
    cart: [],
    notice: '',
    noticeTimer: null,

    // Elegir presentación (unidad / media caja / caja)
    chooser: null,

    // Pesar (kilo / litro)
    weigh: null,
    weighQty: '',
    weighAmount: '',

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

    /** Para la vista: "3,50" → 3.5 */
    toNumber(value) {
        return toNumber(value);
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

    product(id) {
        return this.products.find((p) => p.id === id);
    },

    // ── Catálogo ─────────────────────────────────────────────
    get results() {
        const raw = this.search.trim();
        const term = normalize(raw);
        let list = this.products;

        if (term) {
            const words = term.split(/\s+/);
            list = list.filter((p) =>
                p.barcode === raw
                || p.presentations.some((pr) => pr.barcode === raw)
                || words.every((w) => p._search.includes(w)));
        } else if (this.category) {
            list = list.filter((p) => p.category === this.category);
        }

        return list.slice(0, 60);
    },

    /**
     * Enter en el buscador. El código de la botella agrega 1 unidad; el código de la caja agrega 1 caja.
     * Si no es un código y hay un único resultado, se toca ese producto.
     */
    submitSearch() {
        const term = this.search.trim();
        if (!term) return;

        for (const product of this.products) {
            if (product.barcode && product.barcode === term) {
                this.search = '';
                product.fractional ? this.openWeigh(product) : this.addLine(product, null, 1);
                return;
            }
            const presentation = product.presentations.find((pr) => pr.barcode && pr.barcode === term);
            if (presentation) {
                this.search = '';
                this.addLine(product, presentation, 1);
                return;
            }
        }

        if (this.results.length === 1) {
            this.add(this.results[0]);
            this.search = '';
        } else if (this.results.length === 0) {
            this.flash(`No encontré «${term}». Revisa cómo está escrito.`);
            this.search = '';
        } else {
            this.flash('Hay varios productos con ese nombre: toca el que quieres.');
        }
    },

    /** Tocar un producto: pesa, pregunta la presentación o agrega 1 unidad */
    add(product) {
        if (product.stock <= 0) {
            this.flash(`«${product.name}» no tiene stock.`);
            return;
        }
        if (product.fractional) {
            this.openWeigh(product);
        } else if (product.presentations.length) {
            this.chooser = product;
        } else {
            this.addLine(product, null, 1);
        }
    },

    choose(presentation) {
        const product = this.chooser;
        this.chooser = null;
        this.addLine(product, presentation, 1);
    },

    // ── Pesar ────────────────────────────────────────────────
    openWeigh(product) {
        this.weigh = product;
        this.weighQty = '';
        this.weighAmount = '';
        this.$nextTick(() => this.$refs.weighQty?.focus());
    },

    /** "Por S/ 2": calcula el peso con el precio por kilo */
    weighByAmount() {
        const amount = toNumber(this.weighAmount);
        this.weighQty = amount > 0 ? String(round3(amount / this.weigh.price)) : '';
    },

    get weighTotal() {
        return this.weigh ? round2(toNumber(this.weighQty) * this.weigh.price) : 0;
    },

    confirmWeigh() {
        const qty = round3(toNumber(this.weighQty));
        if (qty <= 0) {
            this.flash('Escribe cuánto marcó la balanza.');
            return;
        }
        const product = this.weigh;
        this.weigh = null;
        this.addLine(product, null, qty);
    },

    // ── Carrito ──────────────────────────────────────────────
    /** Stock (en unidad base) que ya está en el carrito para un producto, sin contar una línea */
    usedUnits(productId, except = null) {
        return this.cart
            .filter((l) => l.productId === productId && l !== except)
            .reduce((sum, l) => sum + l.qty * l.units, 0);
    },

    /** ¿Alcanza el stock para que esta línea tenga `qty`? */
    fits(product, units, qty, line = null) {
        return round3(this.usedUnits(product.id, line) + qty * units) <= product.stock;
    },

    stockMessage(product) {
        return `Solo quedan ${this.qtyLabel(product.stock)} ${product.fractional ? product.unit.toLowerCase() + 's' : ''} de «${product.name}».`.replace('  ', ' ');
    },

    addLine(product, presentation, qty) {
        const key = `${product.id}:${presentation?.id ?? 0}`;
        const line = this.cart.find((l) => l.key === key);
        const units = presentation ? presentation.units : 1;
        const next = round3((line ? line.qty : 0) + qty);

        if (!this.fits(product, units, next, line)) {
            this.flash(this.stockMessage(product));
            this.focusSearch();
            return;
        }

        if (line) {
            line.qty = next;
            // La línea que se tocó sube arriba para que se vea
            this.cart = [line, ...this.cart.filter((l) => l !== line)];
        } else {
            this.cart.unshift({
                key,
                productId: product.id,
                presentationId: presentation?.id ?? null,
                name: product.name,
                detail: presentation ? `${presentation.name} x${this.qtyLabel(presentation.units)}` : null,
                listPrice: presentation ? presentation.price : product.price,
                price: presentation ? presentation.price : product.price,
                units,
                fractional: !presentation && product.fractional,
                unit: product.unit,
                qty,
            });
        }
        this.focusSearch();
    },

    increase(line) {
        const product = this.product(line.productId);
        if (!this.fits(product, line.units, line.qty + 1, line)) {
            this.flash(this.stockMessage(product));
            return;
        }
        line.qty = round3(line.qty + 1);
    },

    decrease(line) {
        if (line.qty - 1 <= 0) {
            this.remove(line);
            return;
        }
        line.qty = round3(line.qty - 1);
    },

    /** Cambiar el peso de una línea por kilo/litro */
    setQty(line, value) {
        const product = this.product(line.productId);
        const qty = round3(toNumber(value));
        if (qty <= 0) return;
        if (!this.fits(product, line.units, qty, line)) {
            this.flash(this.stockMessage(product));
            line.qty = round3(product.stock - this.usedUnits(product.id, line));
            return;
        }
        line.qty = qty;
    },

    remove(line) {
        this.cart = this.cart.filter((l) => l !== line);
        this.focusSearch();
    },

    clearCart() {
        this.cart = [];
        this.discountValue = '';
        this.focusSearch();
    },

    lineTotal(line) {
        return round2(line.price * line.qty);
    },

    // ── Rebajas y descuento ──────────────────────────────────
    openPrice(line) {
        if (!this.settings.allowDiscounts) {
            this.flash('La tienda no permite rebajar precios.');
            return;
        }
        this.priceLine = line;
        this.priceInput = line.price === line.listPrice ? '' : String(line.price);
        this.$nextTick(() => this.$refs.priceInput?.focus());
    },

    savePrice() {
        const price = round2(toNumber(this.priceInput));
        const line = this.priceLine;
        if (!line) return;
        if (this.priceInput === '' || price === line.listPrice) {
            line.price = line.listPrice;
        } else if (price <= 0) {
            this.flash('El precio debe ser mayor que cero.');
            return;
        } else if (price > line.listPrice) {
            this.flash(`No se puede cobrar más caro que ${this.money(line.listPrice)}.`);
            return;
        } else {
            line.price = price;
        }
        this.priceLine = null;
        this.focusSearch();
    },

    openDiscount() {
        if (!this.settings.allowDiscounts) {
            this.flash('La tienda no permite descuentos.');
            return;
        }
        this.discountDraft = this.discountValue;
        this.discountOpen = true;
        this.$nextTick(() => this.$refs.discountInput?.focus());
    },

    saveDiscount() {
        this.discountValue = toNumber(this.discountDraft) > 0 ? this.discountDraft : '';
        this.discountOpen = false;
        this.focusSearch();
    },

    /** Suma de las líneas (ya con rebajas), antes del descuento a la venta */
    get subtotal() {
        return round2(this.cart.reduce((sum, line) => sum + this.lineTotal(line), 0));
    },

    /** A precio normal, sin rebajas ni descuentos */
    get gross() {
        return round2(this.cart.reduce((sum, line) => sum + round2(line.listPrice * line.qty), 0));
    },

    get discount() {
        const value = toNumber(this.discountValue);
        if (value <= 0) return 0;
        return this.discountMode === 'percent' ? round2(this.subtotal * value / 100) : round2(value);
    },

    get total() {
        return Math.max(0, round2(this.subtotal - this.discount));
    },

    /** Todo lo que se descontó (rebajas + descuento) */
    get savings() {
        return round2(this.gross - this.total);
    },

    get maxDiscount() {
        return round2(this.gross * this.settings.maxDiscountPercent / 100);
    },

    get overLimit() {
        return this.savings > this.maxDiscount + 0.001 || (this.discount > 0 && this.discount >= this.subtotal);
    },

    /**
     * Redondeo del efectivo al múltiplo de S/ 0.10 (igual que Company::cashRounding en el servidor).
     * Positivo = se le perdona al cliente; negativo = se cobra de más.
     */
    roundingFor(total) {
        const rest = Math.round(total * 100) % 10;
        if (!this.settings.rounding || rest === 0) return 0;
        return this.settings.rounding === 'business' ? -round2((10 - rest) / 100) : round2(rest / 100);
    },

    /** Lo que se cobraría en efectivo (redondeado a S/ 0.10), para mostrarlo antes de elegir cómo paga */
    get cashTotal() {
        return round2(this.total - this.roundingFor(this.total));
    },

    /** Lo que se cobra: efectivo y fiado (se paga después en efectivo) van redondeados */
    get charged() {
        return ['efectivo', 'fiado'].includes(this.method) ? this.cashTotal : this.total;
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
        if (this.overLimit) {
            this.flash(`El descuento pasa del máximo permitido (${this.settings.maxDiscountPercent}% = ${this.money(this.maxDiscount)}).`);
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

    /** Billetes sugeridos para pagar: los que alcanzan */
    get bills() {
        const due = this.charged;
        return [10, 20, 50, 100, 200].filter((b) => b > due).slice(0, 3);
    },

    get change() {
        const received = toNumber(this.received);
        return received >= this.charged ? round2(received - this.charged) : null;
    },

    get missing() {
        const received = toNumber(this.received);
        return this.received !== '' && received < this.charged ? round2(this.charged - received) : 0;
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

    /** ¿Se puede confirmar? (evita errores obvios antes de enviar) */
    get canConfirm() {
        if (!this.method || this.overLimit) return false;
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
                items: this.cart.map((l) => ({
                    product_id: l.productId,
                    presentation_id: l.presentationId,
                    quantity: l.qty,
                    price: l.price !== l.listPrice ? l.price : null,   // solo si se rebajó
                })),
                discount_amount: this.discountMode === 'amount' && this.discount > 0 ? this.discount : null,
                discount_percent: this.discountMode === 'percent' && this.discount > 0 ? toNumber(this.discountValue) : null,
                voucher_type: this.voucher,
                payment_method: this.method,
                amount_received: this.method === 'efectivo' && this.received !== '' ? toNumber(this.received) : null,

                client_id: this.customer.id,
                customer_name: this.customer.name || null,
                customer_document: this.customer.document || null,
                customer_address: this.customer.address || null,
                customer_phone: this.customer.phone || null,
            });

            // Stock actualizado de lo vendido
            Object.entries(data.stock).forEach(([id, stock]) => {
                const product = this.product(Number(id));
                if (product) product.stock = stock;
            });

            this.result = data;
            this.step = 'done';
            this.cart = [];
            this.discountValue = '';
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
