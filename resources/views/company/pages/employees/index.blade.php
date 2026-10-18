@extends('company.layouts.base')

@section('title', 'Empleados')

@section('content')
<x-page-header title="Empleados" subtitle="Personal que entra al panel de empleado (/employee/login)">
    <a href="{{ route('company.employees.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
        <i class="fas fa-plus text-xs"></i> Nuevo empleado
    </a>
</x-page-header>

<x-search-form :action="route('company.employees.index')" placeholder="Nombre o correo..." />

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    @if($employees->isEmpty())
        <div class="flex flex-col items-center justify-center py-16">
            <div class="w-14 h-14 bg-slate-100 rounded-xl flex items-center justify-center mb-3">
                <i class="fas fa-users text-slate-300 text-xl"></i>
            </div>
            <p class="text-sm font-medium text-slate-500">Sin empleados</p>
            <p class="text-xs text-slate-400 mt-1">{{ request()->filled('buscar') ? 'Nada coincide con la búsqueda' : 'Registra a tu primer empleado' }}</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        <th class="text-left px-5 py-3">Nombre</th>
                        <th class="text-left px-4 py-3">Correo</th>
                        <th class="text-center px-4 py-3">Estado</th>
                        <th class="text-center px-5 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($employees as $employee)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-3 font-semibold text-slate-800">{{ $employee->name }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs">{{ $employee->email }}</td>
                            <td class="px-4 py-3 text-center"><x-status-badge :active="$employee->active" /></td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('company.employees.edit', $employee) }}" title="Editar"
                                       class="inline-flex w-8 h-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                                        <i class="fas fa-pen text-xs"></i>
                                    </a>
                                    <form method="POST" action="{{ route('company.employees.destroy', $employee) }}"
                                          @submit.prevent="$store.confirm.ask($el, @js("Se eliminará a «{$employee->name}» y ya no podrá entrar. Si solo quieres bloquear su acceso, edítalo y desactívalo."), '¿Eliminar empleado?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Eliminar"
                                                class="inline-flex w-8 h-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                                            <i class="fas fa-trash-can text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $employees->links() }}</div>
        @endif
    @endif
</div>
@endsection
