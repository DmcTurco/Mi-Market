@extends('admin.layouts.base')

@php($editing = isset($company))

@section('title', $editing ? 'Editar empresa' : 'Nueva empresa')

@section('content')
<x-page-header :title="$editing ? $company->name : 'Nueva empresa'"
               :subtitle="$editing ? $company->employees_count . ' empleados · ' . $company->products_count . ' productos' : 'La empresa entrará al panel /company con este correo y contraseña'">
    <a href="{{ route('admin.companies.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
        <i class="fas fa-arrow-left text-xs"></i> Volver
    </a>
</x-page-header>

<form method="POST" action="{{ $editing ? route('admin.companies.update', $company) : route('admin.companies.store') }}"
      class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-3xl" x-data="{ sending: false }" @submit="sending = true">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="p-5 grid gap-4 sm:grid-cols-2">
        <h2 class="sm:col-span-2 text-sm font-semibold text-slate-800">Datos de la tienda</h2>
        <x-field label="Nombre" name="name" required class="sm:col-span-2">
            <x-input name="name" :value="$company->name ?? null" maxlength="150" />
        </x-field>
        <x-field label="RUC" name="ruc" hint="11 dígitos (opcional)">
            <x-input name="ruc" :value="$company->ruc ?? null" maxlength="11" inputmode="numeric" />
        </x-field>
        <x-field label="Teléfono" name="phone">
            <x-input name="phone" :value="$company->phone ?? null" maxlength="30" />
        </x-field>
        <x-field label="Dirección" name="address" class="sm:col-span-2">
            <x-input name="address" :value="$company->address ?? null" maxlength="200" />
        </x-field>

        <h2 class="sm:col-span-2 text-sm font-semibold text-slate-800 pt-4 border-t border-slate-100">Acceso al panel</h2>
        <x-field label="Correo" name="email" required class="sm:col-span-2">
            <x-input type="email" name="email" :value="$company->email ?? null" autocomplete="off" />
        </x-field>
        <x-field label="Contraseña" name="password" :required="!$editing" :hint="$editing ? 'Déjala vacía para no cambiarla' : 'Mínimo 8 caracteres'">
            <x-password-input name="password" autocomplete="new-password" />
        </x-field>
        <x-field label="Repite la contraseña" name="password_confirmation" :required="!$editing">
            <x-password-input name="password_confirmation" autocomplete="new-password" />
        </x-field>
    </div>

    <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 rounded-b-xl flex justify-end gap-3">
        <a href="{{ route('admin.companies.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancelar</a>
        <button type="submit" :disabled="sending"
                class="px-5 py-2 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 disabled:opacity-60 rounded-lg flex items-center gap-2">
            <i class="fas fa-spinner fa-spin" x-show="sending" x-cloak></i>
            {{ $editing ? 'Guardar cambios' : 'Registrar empresa' }}
        </button>
    </div>
</form>
@endsection
