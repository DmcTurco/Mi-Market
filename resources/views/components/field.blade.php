{{-- Campo de formulario: etiqueta + control (slot) + error de validación del servidor --}}
@props(['label', 'name', 'required' => false, 'hint' => null])

<div {{ $attributes }}>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700 mb-1.5">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
    </label>
    {{ $slot }}
    @if($hint)
        <p class="text-xs text-slate-400 mt-1">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="text-xs text-red-600 mt-1 flex items-center gap-1">
            <i class="fas fa-circle-exclamation"></i> {{ $message }}
        </p>
    @enderror
</div>
