{{-- Aviso global (Alpine store "alert", ver resources/js/app.js).
     Se abre solo con session('success') o session('error'); los errores de validación
     se muestran junto a cada campo del formulario. --}}
@php
    $alert = null;
    if (session('error')) {
        $alert = ['title' => 'No se pudo completar la acción', 'messages' => [session('error')], 'type' => 'error'];
    } elseif (session('success')) {
        $alert = ['title' => '¡Listo!', 'messages' => [session('success')], 'type' => 'success'];
    }
@endphp

@if($alert)
    <div x-data x-init="$store.alert.show(@js($alert['title']), @js($alert['messages']), @js($alert['type']))"></div>
@endif

<div x-data x-show="$store.alert.open" x-cloak x-transition.opacity
     @keydown.escape.window="$store.alert.close()"
     class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div @click.outside="$store.alert.close()" class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                 :class="$store.alert.type === 'success' ? 'bg-emerald-100' : 'bg-red-100'">
                <i class="fas" :class="$store.alert.type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-red-500'"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800" x-text="$store.alert.title"></h3>
        </div>
        <ul class="px-5 py-4 space-y-2 text-sm text-slate-700 max-h-72 overflow-auto">
            <template x-for="message in $store.alert.messages">
                <li class="flex items-start gap-2">
                    <i class="fas text-xs mt-1" :class="$store.alert.type === 'success' ? 'fa-check text-emerald-500' : 'fa-circle-exclamation text-red-400'"></i>
                    <span x-text="message"></span>
                </li>
            </template>
        </ul>
        <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
            <button type="button" @click="$store.alert.close()"
                    class="px-5 py-2 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg transition-colors">
                Entendido
            </button>
        </div>
    </div>
</div>
