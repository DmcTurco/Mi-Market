@extends('employee.layouts.base')

@section('title', 'Mercadería registrada')

@section('content')
<x-page-header title="Mercadería registrada">
    <a href="{{ route('employee.purchases.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-lg">
        <i class="fas fa-plus"></i> Registrar otra
    </a>
</x-page-header>

@include('shared.purchase-detail')
@endsection
