/**
 * Entrada táctil (tablets / celulares) para las pantallas marcadas con data-touch-input.
 *
 * En un dispositivo táctil el teclado del sistema se abría solo cada vez que un campo recibía el foco.
 * Aquí los campos dejan de abrirlo (inputmode="none") y se ofrece:
 *  - Campos numéricos (cantidad, monto recibido, precio...): un teclado numérico propio, abajo en pantalla.
 *  - Campos de texto (buscador, nombre...): un botoncito de teclado junto al campo, que lo abre solo si se toca.
 * En una PC con mouse no cambia nada. El lector de código de barras y el teclado físico siguen funcionando igual.
 */
const NUMERIC_MODES = ['decimal', 'numeric', 'tel'];
const SKIP_TYPES = ['checkbox', 'radio', 'hidden', 'button', 'submit', 'reset', 'file', 'range', 'color',
    'date', 'time', 'datetime-local', 'month', 'week'];

const KEY = 'h-14 rounded-xl text-2xl font-semibold select-none touch-manipulation active:scale-95 transition ';
const KEY_NUM = KEY + 'bg-white text-slate-800 border border-slate-200 active:bg-slate-100';
const KEY_ACT = KEY + 'bg-slate-200 text-slate-700 active:bg-slate-300';

// Algunas tablets (Windows, o con mouse conectado) no reportan "pointer: coarse"; se acepta cualquier pantalla táctil.
const isTouchDevice = () => (window.matchMedia?.('(pointer: coarse), (any-pointer: coarse)').matches ?? false)
    || (navigator.maxTouchPoints ?? 0) > 0
    || 'ontouchstart' in window;

export default function setupTouchInput() {
    if (!isTouchDevice()) return;

    const roots = document.querySelectorAll('[data-touch-input]');
    if (!roots.length) return;

    let active = null;      // campo numérico al que escribe el teclado
    let textField = null;   // campo de texto enfocado (para el botón de teclado)
    let watcher = null;

    // ── Teclado numérico ─────────────────────────────────────
    const pad = document.createElement('div');
    pad.hidden = true;
    pad.className = 'fixed bottom-0 inset-x-0 sm:left-auto sm:right-3 sm:bottom-3 sm:w-80 z-[100] bg-slate-100 sm:rounded-2xl '
        + 'shadow-2xl border-t sm:border border-slate-300 p-3';
    pad.innerHTML = `
        <div class="flex items-center gap-2 mb-2">
            <div data-display class="flex-1 min-w-0 h-12 px-3 flex items-center justify-end rounded-xl bg-white border border-slate-200 text-2xl font-bold text-slate-800 truncate"></div>
            <button type="button" data-key="close" aria-label="Cerrar" class="w-12 h-12 rounded-xl bg-white border border-slate-200 text-slate-500 text-lg active:bg-slate-100">
                <i class="fas fa-chevron-down pointer-events-none"></i>
            </button>
        </div>
        <div class="grid grid-cols-4 gap-2">
            ${['7', '8', '9'].map((k) => `<button type="button" data-key="${k}" class="${KEY_NUM}">${k}</button>`).join('')}
            <button type="button" data-key="back" aria-label="Borrar" class="${KEY_ACT}"><i class="fas fa-delete-left pointer-events-none"></i></button>
            ${['4', '5', '6'].map((k) => `<button type="button" data-key="${k}" class="${KEY_NUM}">${k}</button>`).join('')}
            <button type="button" data-key="clear" class="${KEY_ACT} text-lg!">C</button>
            ${['1', '2', '3'].map((k) => `<button type="button" data-key="${k}" class="${KEY_NUM}">${k}</button>`).join('')}
            <button type="button" data-key="." class="${KEY_NUM}">.</button>
            <button type="button" data-key="0" class="${KEY_NUM} col-span-4">0</button>
        </div>`;
    document.body.appendChild(pad);

    const display = pad.querySelector('[data-display]');
    const dotKey = pad.querySelector('[data-key="."]');

    const refreshDisplay = () => {
        if (!active) return;
        display.textContent = active.value || active.placeholder || '0';
        display.classList.toggle('text-slate-400!', !active.value);
    };

    const fire = (el, type) => el.dispatchEvent(new Event(type, { bubbles: true }));

    const press = (key) => {
        const el = active;
        if (!el) return;

        const value = el.value;
        let start = el.selectionStart ?? value.length;
        let end = el.selectionEnd ?? value.length;

        if (key === 'clear') {
            el.value = '';
            start = 0;
        } else if (key === 'back') {
            if (start !== end) {
                el.value = value.slice(0, start) + value.slice(end);
            } else if (start > 0) {
                el.value = value.slice(0, start - 1) + value.slice(end);
                start -= 1;
            }
        } else {
            const rest = value.slice(0, start) + value.slice(end);
            if (key === '.' && (el.dataset.numpad !== 'decimal' || rest.includes('.'))) return;
            el.value = value.slice(0, start) + key + value.slice(end);
            start += 1;
        }

        try {
            el.setSelectionRange(start, start);
        } catch (e) {}
        fire(el, 'input');
        refreshDisplay();
    };

    const closePad = () => {
        clearInterval(watcher);
        if (active) {
            fire(active, 'change');
            active.blur();
        }
        active = null;
        pad.hidden = true;
    };

    const openPad = (el) => {
        active = el;
        dotKey.classList.toggle('opacity-30', el.dataset.numpad !== 'decimal');
        pad.hidden = false;
        refreshDisplay();

        // Si el campo quedó escondido (se cerró la ventana) o ya no existe, se cierra el teclado.
        clearInterval(watcher);
        watcher = setInterval(() => {
            if (!active || !active.isConnected || active.offsetParent === null) closePad();
        }, 300);

        // Que el campo no quede tapado por el teclado.
        requestAnimationFrame(() => el.scrollIntoView({ block: 'center', behavior: 'smooth' }));
    };

    // pointerdown + preventDefault: tocar una tecla no le quita el foco al campo.
    pad.addEventListener('pointerdown', (event) => {
        event.preventDefault();
        const key = event.target.closest('[data-key]')?.dataset.key;
        if (!key) return;
        key === 'close' ? closePad() : press(key);
    });

    // Las ventanas del sistema se cierran con "click.outside" (Alpine); tocar el teclado no debe contar como "afuera".
    pad.addEventListener('click', (event) => event.stopPropagation());

    // ── Botón para abrir el teclado del sistema ──────────────
    const kbButton = document.createElement('button');
    kbButton.type = 'button';
    kbButton.hidden = true;
    kbButton.setAttribute('aria-label', 'Abrir teclado');
    kbButton.className = 'fixed z-[100] rounded-full bg-slate-800 text-white shadow-lg flex items-center justify-center '
        + 'active:bg-slate-700 touch-manipulation';
    kbButton.innerHTML = '<i class="fas fa-keyboard pointer-events-none"></i>';
    document.body.appendChild(kbButton);

    const placeKbButton = () => {
        if (!textField) return;
        const rect = textField.getBoundingClientRect();
        const visible = rect.width > 0 && rect.bottom > 0 && rect.top < window.innerHeight;
        kbButton.hidden = !visible;
        if (!visible) return;
        // Dentro del campo, en su borde derecho. En campos de varias líneas, arriba a la derecha.
        const size = Math.max(28, Math.min(40, rect.height - 8));
        const top = textField.tagName === 'TEXTAREA' ? rect.top + 4 : rect.top + (rect.height - size) / 2;
        kbButton.style.width = kbButton.style.height = `${size}px`;
        kbButton.style.top = `${top}px`;
        kbButton.style.left = `${rect.right - size - 8}px`;
    };

    const hideKbButton = () => {
        kbButton.classList.replace('bg-brand-600', 'bg-slate-800');
        textField = null;
        kbButton.hidden = true;
    };

    kbButton.addEventListener('pointerdown', (event) => event.preventDefault());
    kbButton.addEventListener('click', (event) => {
        event.stopPropagation();    // para que "click.outside" de las ventanas no lo cuente como afuera
        const el = textField;
        if (!el) return;
        el.readOnly = false;
        el.setAttribute('inputmode', el.dataset.realMode || 'text');
        el.dataset.kbd = '1';
        el.dataset.kbdOpening = '1';    // el blur de abajo no debe volver a bloquear el teclado
        el.blur();
        el.focus();     // al volver a enfocar con inputmode normal, el sistema muestra su teclado
        delete el.dataset.kbdOpening;
        // Chrome en Android: pide el teclado de forma explícita (si el navegador lo soporta).
        const askKeyboard = () => {
            try {
                navigator.virtualKeyboard?.show();
            } catch (e) {}
        };
        askKeyboard();
        // Algunos navegadores solo muestran el teclado un instante después; si no, tocar el campo (ya desbloqueado) lo abre.
        setTimeout(() => {
            if (el.dataset.kbd && document.activeElement !== el) el.focus();
            askKeyboard();
        }, 80);
        kbButton.classList.replace('bg-slate-800', 'bg-brand-600');
        placeKbButton();
    });

    window.addEventListener('resize', placeKbButton);
    window.addEventListener('scroll', placeKbButton, true);

    // ── Preparar los campos ──────────────────────────────────
    const prepare = (el) => {
        if (el.dataset.touchReady || SKIP_TYPES.includes(el.type) || el.readOnly) return;
        el.dataset.touchReady = '1';

        let mode = el.getAttribute('inputmode');
        if (el.type === 'number') {
            // Un type=number no deja escribir "3." a medias; como texto numérico sí.
            mode = /^\d+$/.test(el.getAttribute('step') ?? '') ? 'numeric' : 'decimal';
            el.type = 'text';
        }
        el.dataset.numpad = NUMERIC_MODES.includes(mode) ? mode : '';
        el.dataset.realMode = mode && mode !== 'none' ? mode : 'text';
        el.setAttribute('inputmode', 'none');
        // inputmode="none" no lo respetan todos los navegadores; con readonly el teclado no sale en ninguno.
        el.readOnly = true;

        // Deja sitio dentro del campo para el botón de teclado.
        if (!el.dataset.numpad && parseFloat(getComputedStyle(el).paddingRight) < 48) el.style.paddingRight = '48px';
    };

    const prepareAll = (root) => root.querySelectorAll('input, textarea').forEach(prepare);

    roots.forEach((root) => {
        prepareAll(root);
        new MutationObserver(() => prepareAll(root)).observe(root, { childList: true, subtree: true });
    });

    const onFocus = (el) => {
        if (!(el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement) || !el.closest('[data-touch-input]')) return;

        // Campos creados por Alpine (filas nuevas) y enfocados por código antes de que el observador los prepare.
        prepare(el);
        if (!el.dataset.touchReady) return;

        if (el.dataset.numpad) {
            hideKbButton();
            openPad(el);
        } else {
            pad.hidden = true;
            active = null;
            textField = el;
            kbButton.classList.replace('bg-brand-600', 'bg-slate-800');
            placeKbButton();
        }
    };

    document.addEventListener('focusin', (event) => onFocus(event.target));

    // Un campo con autofocus ya tenía el foco antes de que este código arrancara.
    if (document.activeElement && document.activeElement !== document.body) onFocus(document.activeElement);

    // Tocar un campo numérico que ya tenía el foco (el teclado se había cerrado) lo vuelve a abrir.
    document.addEventListener('click', (event) => {
        const el = event.target;
        if (el instanceof HTMLInputElement && el.dataset.numpad && pad.hidden) openPad(el);
    });

    // Al salir de un campo se vuelve a bloquear el teclado del sistema para el próximo toque.
    document.addEventListener('focusout', (event) => {
        const el = event.target;
        if (el.dataset?.touchReady && !el.dataset.kbdOpening) {
            delete el.dataset.kbd;
            el.setAttribute('inputmode', 'none');
            el.readOnly = true;
        }
    });

    // Teclado físico o lector de código de barras: al llegar una tecla, el campo se libera para que escriba normal.
    document.addEventListener('keydown', (event) => {
        const el = event.target;
        if (el.dataset?.touchReady && el.readOnly
            && (event.key.length === 1 || event.key === 'Backspace' || event.key === 'Delete')
            && !event.ctrlKey && !event.metaKey) {
            el.readOnly = false;
        }
    }, true);

    // Tocar fuera de un campo (un producto, un botón...) esconde el teclado numérico y el botón.
    document.addEventListener('pointerdown', (event) => {
        const target = event.target;
        if (pad.contains(target) || kbButton.contains(target)) return;
        if (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement) return;

        if (active) closePad();
        if (textField && !target.closest('label')) hideKbButton();
    }, true);

    // Mantiene el valor del visor al día si el campo cambia por otro lado.
    document.addEventListener('input', (event) => {
        if (event.target === active) refreshDisplay();
    });
}
