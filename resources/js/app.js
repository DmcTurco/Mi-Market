import './bootstrap';
import Alpine from 'alpinejs';
import pos from './pos';
import purchaseForm from './purchase-form';

Alpine.data('pos', pos);
Alpine.data('purchaseForm', purchaseForm);

/**
 * Estructura de los paneles: menú lateral deslizable en móvil y plegable en pantallas grandes.
 * El plegado es la clase sidebar-collapsed de <html> (CSS puro, ver app.css) y se recuerda en este navegador.
 */
Alpine.data('panelLayout', () => ({
    sidebarOpen: false,

    toggleCollapsed() {
        const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        try {
            localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0');
        } catch (e) {}
    },
}));

/**
 * Aviso global (éxito / errores). El layout lo abre con lo que respondió el controlador;
 * desde cualquier vista: $store.alert.show('Título', ['mensaje'], 'success' | 'error').
 * El de éxito se cierra solo; los errores esperan a que el usuario los lea.
 */
Alpine.store('alert', {
    open: false,
    type: 'error',
    title: '',
    messages: [],
    timer: null,

    show(title, messages, type = 'error') {
        this.type = type;
        this.title = title || (type === 'success' ? '¡Listo!' : 'Revisa los datos ingresados');
        this.messages = messages || [];
        this.open = true;

        clearTimeout(this.timer);
        if (type === 'success') {
            this.timer = setTimeout(() => this.close(), 4000);
        }
    },

    close() {
        clearTimeout(this.timer);
        this.open = false;
    },
});

/**
 * Confirmación previa para acciones delicadas (eliminar, desactivar...).
 * Uso: <form x-data @submit.prevent="$store.confirm.ask($el, '¿Eliminar la categoría X?')">
 */
Alpine.store('confirm', {
    open: false,
    title: '',
    message: '',
    okLabel: 'Aceptar',
    form: null,

    ask(form, message, title = '¿Confirmar acción?', okLabel = 'Eliminar') {
        this.form = form;
        this.message = message;
        this.title = title;
        this.okLabel = okLabel;
        this.open = true;
    },

    accept() {
        const form = this.form;
        this.cancel();
        if (form) form.submit();
    },

    cancel() {
        this.open = false;
        this.form = null;
    },
});

/**
 * Montos: en Perú muchos escriben "3,50". Los campos inputmode="decimal" cambian la coma por punto al vuelo.
 */
document.addEventListener('input', (event) => {
    const input = event.target;
    if (input instanceof HTMLInputElement && input.inputMode === 'decimal' && input.value.includes(',')) {
        input.value = input.value.replace(/,/g, '.');
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }
});

window.Alpine = Alpine;
Alpine.start();
