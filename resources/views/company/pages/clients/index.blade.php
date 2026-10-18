@extends('company.layouts.base')

@section('title', 'Fiados')

@section('content')
<x-page-header title="Fiados" subtitle="Clientes que te deben. Toca uno para ver su cuenta o registrar un pago." />

<x-search-form :action="route('company.clients.index')" placeholder="Nombre, DNI o celular..." />

@include('shared.client-list', ['showUrl' => fn ($client) => route('company.clients.show', $client), 'indexUrl' => route('company.clients.index')])
@endsection
