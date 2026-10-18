@extends('company.layouts.base')

@php($editing = isset($employee))

@section('title', $editing ? 'Editar empleado' : 'Nuevo empleado')

@section('content')
<x-page-header :title="$editing ? $employee->name : 'Nuevo empleado'"
               subtitle="Entrará al panel /employee/login con este correo y contraseña">
    <a href="{{ route('company.employees.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
        <i class="fas fa-arrow-left text-xs"></i> Volver
    </a>
</x-page-header>

<form method="POST" action="{{ $editing ? route('company.employees.update', $employee) : route('company.employees.store') }}"
      class="bg-white rounded-xl border border-slate-200 shadow-sm" x-data="{ sending: false }" @submit="sending = true">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="p-5 grid gap-4 sm:grid-cols-2">
        <x-field label="Nombre" name="name" required class="sm:col-span-2">
            <x-input name="name" :value="$employee->name ?? null" maxlength="150" />
        </x-field>
        <x-field label="Correo" name="email" required class="sm:col-span-2">
            <x-input type="email" name="email" :value="$employee->email ?? null" autocomplete="off" />
        </x-field>
        <x-field label="Contraseña" name="password" :required="!$editing" :hint="$editing ? 'Déjala vacía para no cambiarla' : 'Mínimo 8 caracteres'">
            <x-password-input name="password" autocomplete="new-password" />
        </x-field>
        <x-field label="Repite la contraseña" name="password_confirmation" :required="!$editing">
            <x-password-input name="password_confirmation" autocomplete="new-password" />
        </x-field>

        <div class="sm:col-span-2 pt-2" x-data="{ active: @js((bool) old('active', $employee->active ?? true)) }">
            <input type="hidden" name="active" value="0">
            <label class="flex items-start gap-3 cursor-pointer select-none">
                <input type="checkbox" name="active" value="1" x-model="active" class="mt-0.5 w-4 h-4 accent-brand-600">
                <span>
                    <span class="block text-sm font-medium text-slate-700">Puede iniciar sesión</span>
                    <span class="block text-xs" :class="active ? 'text-slate-400' : 'text-amber-600'"
                          x-text="active ? 'El empleado entra normalmente.' : 'Desactivado: no podrá entrar al panel.'"></span>
                </span>
            </label>
        </div>
    </div>

    <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 rounded-b-xl flex justify-end gap-3">
        <a href="{{ route('company.employees.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancelar</a>
        <button type="submit" :disabled="sending"
                class="px-5 py-2 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 disabled:opacity-60 rounded-lg flex items-center gap-2">
            <i class="fas fa-spinner fa-spin" x-show="sending" x-cloak></i>
            {{ $editing ? 'Guardar cambios' : 'Registrar empleado' }}
        </button>
    </div>
</form>
@endsection
