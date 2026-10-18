@extends('admin.layouts.base')

@section('title', 'Inicio')

@section('content')
<x-page-header title="Panel de Administración" subtitle="Resumen de todas las tiendas del sistema" />

<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
    <x-stat-card label="Empresas" :value="$stats['companies']" icon="fa-building" hint="Registradas en el sistema" />
    <x-stat-card label="Empleados" :value="$stats['employees']" icon="fa-users" hint="En todas las empresas" />
    <x-stat-card label="Productos" :value="$stats['products']" icon="fa-boxes-stacked" hint="En todas las empresas" />
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-800">Últimas empresas registradas</h2>
        <a href="{{ route('admin.companies.create') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">
            <i class="fas fa-plus"></i> Nueva empresa
        </a>
    </div>
    <ul class="divide-y divide-slate-100">
        @forelse($latestCompanies as $company)
            <li class="px-5 py-3 flex items-center gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-800 truncate">{{ $company->name }}</p>
                    <p class="text-xs text-slate-500">{{ $company->email }} · desde {{ $company->created_at?->format('d/m/Y') }}</p>
                </div>
                <a href="{{ route('admin.companies.edit', $company) }}" class="text-xs text-slate-500 hover:text-brand-700">Ver</a>
            </li>
        @empty
            <li class="px-5 py-8 text-center text-sm text-slate-400">Todavía no hay empresas registradas</li>
        @endforelse
    </ul>
</div>
@endsection
