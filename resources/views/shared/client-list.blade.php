{{-- Lista de clientes con su deuda. Variables: $clients, $showUrl (closure), $indexUrl --}}
<div class="flex gap-2 mb-3 text-sm">
    <a href="{{ $indexUrl }}" @class(['px-3 py-1.5 rounded-full border', 'bg-brand-600 text-white border-brand-600' => request('filtro') !== 'todos', 'bg-white border-slate-200' => request('filtro') === 'todos'])>Los que deben</a>
    <a href="{{ $indexUrl }}?filtro=todos" @class(['px-3 py-1.5 rounded-full border', 'bg-brand-600 text-white border-brand-600' => request('filtro') === 'todos', 'bg-white border-slate-200' => request('filtro') !== 'todos'])>Todos los clientes</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden max-w-3xl">
    @forelse($clients as $client)
        <a href="{{ $showUrl($client) }}" class="px-5 py-4 border-b border-slate-100 last:border-0 flex items-center gap-4 hover:bg-slate-50">
            <div class="w-10 h-10 bg-brand-100 rounded-full flex items-center justify-center shrink-0">
                <span class="font-bold text-brand-700">{{ mb_strtoupper(mb_substr($client->name, 0, 1)) }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-slate-800 truncate">{{ $client->name }}</p>
                <p class="text-xs text-slate-500">{{ $client->phone ?: ($client->document_number ?: 'Sin datos de contacto') }}</p>
            </div>
            @if((float) $client->debt_total > 0)
                <span class="font-bold text-amber-700">{{ money($client->debt_total) }}</span>
            @else
                <span class="text-sm text-emerald-600">No debe</span>
            @endif
            <i class="fas fa-chevron-right text-slate-300"></i>
        </a>
    @empty
        <div class="py-16 text-center text-slate-400">
            <i class="fas fa-handshake text-4xl mb-3"></i>
            <p>{{ request('filtro') === 'todos' ? 'Todavía no hay clientes.' : 'Nadie debe nada. 🎉' }}</p>
        </div>
    @endforelse
</div>

@if($clients->hasPages())
    <div class="mt-4 max-w-3xl">{{ $clients->links() }}</div>
@endif
