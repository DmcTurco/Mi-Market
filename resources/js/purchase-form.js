/**
 * "Llegó mercadería" (Alpine): se busca el producto igual que en el punto de venta
 * (nombre o pistola de código de barras + Enter) y se escribe cuántos llegaron y a cuánto.
 * Si el producto tiene cajas, se puede registrar por cajas: 3 cajas de 12 = 36 unidades.
 */
const normalize = (text) => (text ?? '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
const toNumber = (value) => Number(String(value ?? '').replace(',', '.')) || 0;
const moneyFormat = new Intl.NumberFormat('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default (config) => ({
    products: config.products.map((p) => ({ ...p, presentations: p.presentations ?? [], _search: normalize(p.name) })),
    items: config.old ?? [],
    search: '',
    notice: '',
    sending: false,

    init() {
        // Al volver con errores, se recuperan nombre, unidad y presentación de cada fila
        this.items = this.items.map((item) => {
            const product = this.products.find((p) => p.id === Number(item.product_id));
            return product ? this.row(product, item.presentation_id ? Number(item.presentation_id) : '', item.quantity, item.unit_cost) : null;
        }).filter(Boolean);
    },

    row(product, presentationId = '', quantity = '', unitCost = null) {
        const presentation = product.presentations.find((p) => p.id === presentationId);
        return {
            ...product,
            product_id: product.id,
            presentation_id: presentationId,
            quantity,
            unit_cost: unitCost ?? (product.cost ? (product.cost * (presentation?.units ?? 1)).toFixed(2) : ''),
        };
    },

    money(value) {
        return 'S/ ' + moneyFormat.format(Number(value) || 0);
    },

    plural(unit) {
        return { Unidad: 'unidades', Kilo: 'kilos', Litro: 'litros', Paquete: 'paquetes', Caja: 'cajas' }[unit] ?? unit.toLowerCase();
    },

    get results() {
        const raw = this.search.trim();
        const term = normalize(raw);
        if (!term) return [];
        const words = term.split(/\s+/);
        return this.products
            .filter((p) => p.barcode === raw || p.presentations.some((pr) => pr.barcode === raw) || words.every((w) => p._search.includes(w)))
            .slice(0, 8);
    },

    submitSearch() {
        const term = this.search.trim();
        if (!term) return;

        // El código de la caja agrega el producto ya "por cajas"
        for (const product of this.products) {
            if (product.barcode && product.barcode === term) return this.add(product);
            const presentation = product.presentations.find((pr) => pr.barcode && pr.barcode === term);
            if (presentation) return this.add(product, presentation.id);
        }

        if (this.results.length === 1) {
            this.add(this.results[0]);
        } else {
            this.notice = this.results.length ? 'Hay varios productos: toca el que llegó.' : `No encontré «${term}». Si es nuevo, primero créalo en Productos.`;
        }
    },

    add(product, presentationId = '') {
        this.notice = '';
        this.search = '';
        if (!this.items.find((i) => i.product_id === product.id && i.presentation_id === presentationId)) {
            this.items.unshift(this.row(product, presentationId));
        }
        this.$nextTick(() => document.getElementById(`qty-${product.id}-${presentationId || 0}`)?.focus());
    },

    /** Al cambiar entre unidades y cajas, el costo sugerido se ajusta (costo por caja = costo unitario × 12) */
    changePresentation(item) {
        item.presentation_id = item.presentation_id === '' ? '' : Number(item.presentation_id);
        if (item.cost) item.unit_cost = (item.cost * this.unitsOf(item)).toFixed(2);
    },

    unitsOf(item) {
        return item.presentations.find((p) => p.id === item.presentation_id)?.units ?? 1;
    },

    /** "= 36 unidades" para que quede claro cuánto sube el stock */
    baseUnits(item) {
        return toNumber(item.quantity) * this.unitsOf(item);
    },

    remove(item) {
        this.items = this.items.filter((i) => i !== item);
    },

    lineTotal(item) {
        return toNumber(item.quantity) * toNumber(item.unit_cost);
    },

    get total() {
        return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
    },
});
