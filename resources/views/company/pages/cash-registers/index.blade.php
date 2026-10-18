@extends('company.layouts.base')

@section('title', 'Cajas')

@section('content')
<x-page-header title="Cajas" subtitle="Quién está vendiendo ahora y cómo cuadraron los turnos." />

@if($open->isNotEmpty())
    <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2">Abiertas ahora</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
        @foreach($open as $register)
            <a href="{{ route('company.cash-registers.show', $register) }}" class="bg-white rounded-xl border-2 border-emerald-200 p-4 hover:border-emerald-500">
                <p class="font-semibold text-slate-800"><span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-1"></span> {{ $register->employee->name }}</p>
                <p class="text-sm text-slate-500">Desde las {{ $register->opened_at->format('H:i') }} · vendió {{ money($register->summary()['sales_total']) }}</p>
            </a>
        @endforeach
    </div>
@endif

<h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2">Cerradas</h2>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    @if($closed->isEmpty())
        <p class="py-12 text-center text-slate-400">Todavía no se cerró ninguna caja.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        <th class="text-left px-5 py-3">Turno</th>
                        <th class="text-left px-4 py-3">Empleado</th>
                        <th class="text-right px-4 py-3">Debía haber</th>
                        <th class="text-right px-4 py-3">Contó</th>
                        <th class="text-right px-5 py-3">Cuadre</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($closed as $register)
                        <tr class="hover:bg-slate-50 cursor-pointer" onclick="location.href='{{ route('company.cash-registers.show', $register) }}'">
                            <td class="px-5 py-3 text-slate-600">{{ $register->opened_at->format('d/m/Y') }} · {{ $register->opened_at->format('H:i') }} a {{ $register->closed_at->format('H:i') }}</td>
                            <td class="px-4 py-3">{{ $register->employee->name }}</td>
                            <td class="px-4 py-3 text-right">{{ money($register->expected_amount) }}</td>
                            <td class="px-4 py-3 text-right">{{ money($register->closing_amount) }}</td>
                            <td class="px-5 py-3 text-right font-semibold">
                                @php($diff = (float) $register->difference)
                                @if(abs($diff) < 0.005)
                                    <span class="text-emerald-600"><i class="fas fa-check"></i> Cuadra</span>
                                @elseif($diff > 0)
                                    <span class="text-sky-600">Sobró {{ money($diff) }}</span>
                                @else
                                    <span class="text-red-600">Faltó {{ money(abs($diff)) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($closed->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $closed->links() }}</div>
        @endif
    @endif
</div>
@endsection
