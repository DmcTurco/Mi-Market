{{-- Buscador de los listados (GET ?buscar=...) --}}
@props(['action', 'placeholder' => 'Buscar...'])

<form method="GET" action="{{ $action }}" class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm flex gap-3 mb-4">
    <div class="flex-1 relative">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="{{ $placeholder }}"
               class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg flex items-center gap-2">
        <i class="fas fa-search text-xs"></i> Buscar
    </button>
    @if(request()->filled('buscar'))
        <a href="{{ $action }}" class="px-3 py-2 text-sm text-slate-500 hover:text-slate-700 flex items-center" title="Limpiar búsqueda">
            <i class="fas fa-xmark"></i>
        </a>
    @endif
</form>
