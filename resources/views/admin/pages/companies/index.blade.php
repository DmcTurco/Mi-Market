@extends('admin.layouts.base')

@section('title', 'Empresas')

@section('content')
<x-page-header title="Empresas" subtitle="Tiendas que usan el sistema y su usuario de acceso">
    <a href="{{ route('admin.companies.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
        <i class="fas fa-plus text-xs"></i> Nueva empresa
    </a>
</x-page-header>

<x-search-form :action="route('admin.companies.index')" placeholder="Nombre, correo o RUC..." />

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    @if($companies->isEmpty())
        <div class="flex flex-col items-center justify-center py-16">
            <div class="w-14 h-14 bg-slate-100 rounded-xl flex items-center justify-center mb-3">
                <i class="fas fa-building text-slate-300 text-xl"></i>
            </div>
            <p class="text-sm font-medium text-slate-500">Sin empresas</p>
            <p class="text-xs text-slate-400 mt-1">{{ request()->filled('buscar') ? 'Nada coincide con la búsqueda' : 'Registra la primera tienda' }}</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        <th class="text-left px-5 py-3">Empresa</th>
                        <th class="text-left px-4 py-3">Usuario (correo)</th>
                        <th class="text-left px-4 py-3">RUC</th>
                        <th class="text-center px-4 py-3">Empleados</th>
                        <th class="text-center px-4 py-3">Productos</th>
                        <th class="text-center px-5 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($companies as $company)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-3">
                                <p class="font-semibold text-slate-800">{{ $company->name }}</p>
                                <p class="text-xs text-slate-400">Desde {{ $company->created_at?->format('d/m/Y') }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-xs">{{ $company->email }}</td>
                            <td class="px-4 py-3 text-slate-500 font-mono text-xs">{{ $company->ruc ?: '—' }}</td>
                            <td class="px-4 py-3 text-center text-slate-700">{{ $company->employees_count }}</td>
                            <td class="px-4 py-3 text-center text-slate-700">{{ $company->products_count }}</td>
                            <td class="px-5 py-3 text-center">
                                <a href="{{ route('admin.companies.edit', $company) }}" title="Editar"
                                   class="inline-flex w-8 h-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                    <i class="fas fa-pen text-xs"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($companies->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $companies->links() }}</div>
        @endif
    @endif
</div>
@endsection
