@extends('company.layouts.base')

@section('title', 'Llegó mercadería')

@section('content')
<x-page-header title="Llegó mercadería" subtitle="Registra lo que compraste y el stock sube solo.">
    <a href="{{ route('company.purchases.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
        <i class="fas fa-arrow-left text-xs"></i> Volver
    </a>
</x-page-header>

@include('shared.purchase-form', [
    'action'         => route('company.purchases.store'),
    'productsUrl'    => route('company.products.create'),
    'canPayFromCash' => false,
])
@endsection
