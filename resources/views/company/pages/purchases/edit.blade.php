@extends('company.layouts.base')

@section('title', 'Corregir mercadería')

@section('content')
<x-page-header title="Corregir mercadería" :subtitle="'Registrada el ' . $purchase->created_at->format('d/m/Y H:i') . '. El stock se ajusta solo con la diferencia.'">
    <a href="{{ route('company.purchases.show', $purchase) }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
        <i class="fas fa-arrow-left text-xs"></i> Volver
    </a>
</x-page-header>

@include('shared.purchase-form', [
    'action'         => route('company.purchases.update', $purchase),
    'productsUrl'    => route('company.products.create'),
    'canPayFromCash' => false,
])
@endsection
