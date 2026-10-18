/**
 * "Llegó mercadería" (Alpine): se busca el producto igual que en el punto de venta
 * (nombre o pistola de código de barras + Enter) y se escribe cuántos llegaron y a cuánto.
 */
const normalize = (text) => (text ?? '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
const moneyFormat = new Intl.NumberFormat('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default (config) => ({
    products: config.products.map((p) => ({ ...p, _search: normalize(p.name) })),
    items: config.old ?? [],
    search: '',
    notice: '',
    sending: false,

    init() {
        // Al volver con errores, se recuperan nombre y unidad de cada fila
        this.items = this.items.map((item) => {
            const product = this.products.find((p) => p.id === Number(item.product_id));
            return product ? { ...product, product_id: product.id, quantity: item.quantity, unit_cost: item.unit_cost } : null;
        }).filter(Boolean);
    },

    money(value) {
        return 'S/ ' + moneyFormat.format(Number(value) || 0);
    },

    get results() {
        const term = normalize(this.search.trim());
        if (!term) return [];
        const words = term.split(/\s+/);
        return this.products
            .filter((p) => p.barcode === this.search.trim() || words.every((w) => p._search.includes(w)))
            .slice(0, 8);
    },

    submitSearch() {
        const term = this.search.trim();
        if (!term) return;
        const target = this.products.find((p) => p.barcode && p.barcode === term) ?? (this.results.length === 1 ? this.results[0] : null);
        if (target) {
            this.add(target);
        } else {
            this.notice = this.results.length ? 'Hay varios productos: toca el que llegó.' : `No encontré «${term}». Si es nuevo, primero créalo en Productos.`;
        }
    },

    add(product) {
        this.notice = '';
        this.search = '';
        const existing = this.items.find((i) => i.product_id === product.id);
        if (existing) {
            this.$nextTick(() => document.getElementById(`qty-${product.id}`)?.focus());
            return;
        }
        this.items.unshift({ ...product, product_id: product.id, quantity: '', unit_cost: product.cost || '' });
        this.$nextTick(() => document.getElementById(`qty-${product.id}`)?.focus());
    },

    remove(item) {
        this.items = this.items.filter((i) => i !== item);
    },

    lineTotal(item) {
        return (Number(item.quantity) || 0) * (Number(item.unit_cost) || 0);
    },

    get total() {
        return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
    },
});
