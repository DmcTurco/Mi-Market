@extends('layouts.admin')

@section('title')
    <span>Categoria</span>
    <a href="" class="btn btn-primary btn-circle" data-toggle="modal" data-target="#createMdl">
        <i class="fas fa-plus"></i>
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <table id="dt-category" class="table table-striped table-bordered">

                <head>
                    <th>id</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </head>
                <tbody>
                    @if ($category->count() > 0)
                        @foreach ($category as $item)

                        <tr>
                            <th>{{ $item->id }}</th>
                            <th>{{ $item->name }}</th>
                            <th>{{ $item->state }}</th>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="text-center" colspan="9">No se ha encontrado Categorias registradas</td>
                        </tr>
                    @endif

                </tbody>

            </table>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('libs/datatables/dataTables.bootstrap4.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('/libs/datatables/jquery.dataTables.min.js') }}"></script>
@endpush
