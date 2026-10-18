@extends('employee.layouts.base')

@section('title', 'Llegó mercadería')

@section('content')
<x-page-header title="Llegó mercadería" subtitle="Registra lo que trajo el proveedor y el stock sube solo." />

@include('shared.purchase-form', [
    'action'         => route('employee.purchases.store'),
    'productsUrl'    => null,
    'canPayFromCash' => $hasRegister,
])
<p class="mt-3 text-sm text-slate-500 max-w-4xl">¿No encuentras el producto? Pídele al encargado que lo cree en el sistema.</p>
@endsection
