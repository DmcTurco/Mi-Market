{{-- Input de texto con el estilo del panel. Toma el valor anterior (old) si el formulario volvió con errores. --}}
@props(['name', 'type' => 'text', 'value' => null])

<input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}"
       value="{{ $type === 'password' ? '' : old($name, $value) }}"
       {{ $attributes->class([
           'w-full px-3 py-2 text-sm border rounded-lg bg-white transition',
           'focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent',
           'border-red-400' => $errors->has($name),
           'border-slate-300' => !$errors->has($name),
       ]) }}>
