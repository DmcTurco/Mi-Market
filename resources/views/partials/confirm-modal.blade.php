{{-- Confirmación previa (Alpine store "confirm", ver resources/js/app.js).
     Uso: <form ... @submit.prevent="$store.confirm.ask($el, '¿Eliminar X?')"> --}}
<div x-data x-show="$store.confirm.open" x-cloak x-transition.opacity
     @keydown.escape.window="$store.confirm.cancel()"
     class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div @click.outside="$store.confirm.cancel()" class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-5 py-4 flex items-start gap-3">
            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center shrink-0">
                <i class="fas fa-triangle-exclamation text-amber-600"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-800" x-text="$store.confirm.title"></h3>
                <p class="text-sm text-slate-600 mt-1" x-text="$store.confirm.message"></p>
            </div>
        </div>
        <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
            <button type="button" @click="$store.confirm.cancel()"
                    class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                Cancelar
            </button>
            <button type="button" @click="$store.confirm.accept()" x-text="$store.confirm.okLabel"
                    class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
            </button>
        </div>
    </div>
</div>
