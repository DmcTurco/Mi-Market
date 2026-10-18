@extends('employee.layouts.base')

@section('title', 'Fiados')

@section('content')
<x-page-header title="Fiados" subtitle="Clientes que deben. Toca uno para ver su cuenta o cobrarle." />

<x-search-form :action="route('employee.clients.index')" placeholder="Nombre, DNI o celular..." />

@include('shared.client-list', ['showUrl' => fn ($client) => route('employee.clients.show', $client), 'indexUrl' => route('employee.clients.index')])
@endsection
