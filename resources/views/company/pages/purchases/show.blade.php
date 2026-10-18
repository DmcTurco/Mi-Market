@extends('company.layouts.base')

@section('title', 'Mercadería')

@section('content')
<x-page-header title="Mercadería registrada">
    <a href="{{ route('company.purchases.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
        <i class="fas fa-arrow-left text-xs"></i> Volver
    </a>
    <a href="{{ route('company.purchases.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-lg">
        <i class="fas fa-plus"></i> Registrar otra
    </a>
</x-page-header>

@include('shared.purchase-detail')
@endsection
