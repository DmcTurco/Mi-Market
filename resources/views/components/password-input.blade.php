{{-- Contraseña con botón para mostrarla u ocultarla (Alpine) --}}
@props(['name'])

<div x-data="{ show: false }" class="relative">
    <input :type="show ? 'text' : 'password'" type="password" name="{{ $name }}" id="{{ $name }}"
           {{ $attributes->class([
               'w-full pl-3 pr-10 py-2 text-sm border rounded-lg bg-white transition',
               'focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent',
               'border-red-400' => $errors->has($name),
               'border-slate-300' => !$errors->has($name),
           ]) }}>
    <button type="button" tabindex="-1" @click="show = !show"
            :title="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600">
        <i class="fas text-sm" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
    </button>
</div>
