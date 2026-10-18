{{-- Botón grande con ícono para las pantallas de inicio: pensado para usar con el dedo o el mouse sin pensar --}}
@props(['href', 'icon', 'title', 'text' => null, 'primary' => false])

<a href="{{ $href }}" @class([
    'group flex items-center gap-4 p-5 rounded-2xl border-2 transition shadow-sm',
    'bg-brand-600 border-brand-600 text-white hover:bg-brand-700' => $primary,
    'bg-white border-slate-200 hover:border-brand-500 hover:bg-brand-50' => !$primary,
])>
    <div @class([
        'w-14 h-14 rounded-xl flex items-center justify-center shrink-0',
        'bg-white/20' => $primary,
        'bg-brand-100' => !$primary,
    ])>
        <i @class(['fas text-2xl', $icon, 'text-white' => $primary, 'text-brand-700' => !$primary])></i>
    </div>
    <div class="min-w-0">
        <p @class(['text-lg font-bold leading-tight', 'text-slate-800' => !$primary])>{{ $title }}</p>
        @if($text)
            <p @class(['text-sm mt-0.5', 'text-white/80' => $primary, 'text-slate-500' => !$primary])>{{ $text }}</p>
        @endif
    </div>
</a>
