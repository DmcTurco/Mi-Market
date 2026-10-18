{{-- Opción del menú lateral. Con el menú plegado (pantallas grandes) solo se ve el ícono. --}}
@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}" title="{{ trim(strip_tags($slot)) }}"
   {{ $attributes->class([
       'nav-item flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors mb-0.5',
       'bg-brand-50 text-brand-700' => $active,
       'text-slate-600 hover:bg-slate-50 hover:text-slate-800' => !$active,
   ]) }}>
    <i class="fas {{ $icon }} w-4 text-center text-sm"></i>
    <span class="nav-label">{{ $slot }}</span>
</a>
