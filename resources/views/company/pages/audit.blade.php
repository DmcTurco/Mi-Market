@extends('company.layouts.base')

@section('title', 'Bitácora')

@section('content')
<x-page-header title="Bitácora" subtitle="Todo lo que se hizo en tu tienda: quién, qué y cuándo. No se puede borrar ni cambiar." />

<form method="GET" action="{{ route('company.audit.index') }}" class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm flex flex-wrap items-end gap-3 mb-4">
    <label class="text-sm text-slate-600">Desde
        <input type="date" name="desde" value="{{ $from->toDateString() }}" onchange="this.form.submit()" class="block mt-1 px-3 py-2 border border-slate-300 rounded-lg">
    </label>
    <label class="text-sm text-slate-600">Hasta
        <input type="date" name="hasta" value="{{ $to->toDateString() }}" onchange="this.form.submit()" class="block mt-1 px-3 py-2 border border-slate-300 rounded-lg">
    </label>
    <label class="text-sm text-slate-600">Persona
        <select name="persona" onchange="this.form.submit()" class="block mt-1 px-3 py-2 border border-slate-300 rounded-lg bg-white">
            <option value="">Todos</option>
            @foreach($people as $person)
                <option value="{{ $person['value'] }}" @selected(request('persona') === $person['value'])>{{ $person['label'] }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm text-slate-600">Tipo
        <select name="tipo" onchange="this.form.submit()" class="block mt-1 px-3 py-2 border border-slate-300 rounded-lg bg-white">
            <option value="">Todo</option>
            @foreach(array_keys(\App\Models\AuditLog::GROUPS) as $group)
                <option value="{{ $group }}" @selected(request('tipo') === $group)>{{ $group }}</option>
            @endforeach
        </select>
    </label>
    <div class="flex-1 min-w-48 relative">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Ej. anuló, precio, arroz..."
               class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg">Buscar</button>
</form>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    @forelse($logs as $log)
        @php([$icon, $color] = $log->style())
        <div class="px-5 py-3 border-b border-slate-100 last:border-0 flex items-start gap-3" x-data="{ open: false }">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 {{ $color }}">
                <i class="fas {{ $icon }} text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-slate-800">{{ $log->description }}</p>
                <p class="text-xs text-slate-500 mt-0.5">
                    <span class="font-medium">{{ $log->actorLabel() }}</span>
                    · {{ $log->created_at->format('d/m/Y H:i:s') }}
                    @if($log->ip) · IP {{ $log->ip }} @endif
                    @if($log->changes)
                        · <button type="button" @click="open = !open" class="text-brand-700 hover:underline" x-text="open ? 'Ocultar detalle' : 'Ver detalle'"></button>
                    @endif
                </p>
                @if($log->changes)
                    <pre x-show="open" x-cloak class="mt-2 p-3 bg-slate-50 rounded-lg text-xs text-slate-700 whitespace-pre-wrap">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                @endif
            </div>
        </div>
    @empty
        <p class="py-16 text-center text-slate-400">No hay registros con esos filtros.</p>
    @endforelse
</div>

@if($logs->hasPages())
    <div class="mt-4">{{ $logs->links() }}</div>
@endif
@endsection
