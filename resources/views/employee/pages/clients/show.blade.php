@extends('employee.layouts.base')

@section('title', $client->name)

@section('content')
<x-page-header :title="$client->name" subtitle="Cuenta de fiado">
    <a href="{{ route('employee.clients.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
        <i class="fas fa-arrow-left text-xs"></i> Fiados
    </a>
</x-page-header>

@include('shared.client-detail', ['payUrl' => route('employee.clients.pay', $client)])
@endsection
