@extends('layouts.admin')

@section('title')
    <span>Categoria</span>
    <a href="" class="btn btn-primary btn-circle" data-toggle="modal" data-target="#createModal">
        <i class="fas fa-plus"></i>
    </a>
@endsection

@section('content')


    @include('category.modals.create')
    @include('category.modals.delete')
    @include('category.modals.update')

    <div class="card">
        <div class="card-body">
            <table id="dt-category" class="table table-striped table-bordered text-center dts">

                <thead>
                    <th>id</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </thead>
                <tbody>
                    @if ($category->count() > 0)
                        @foreach ($category as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->state }}</td>
                                <td>
                                    <a href="" class="edit-form-data" data-toggle="modal" data-target="#editModal"
                                        onclick="editCategory({{ $item }})">
                                        <i class="far fa-edit"></i></a>
                                    <a href="" class="delete-form-data" data-toggle="modal"
                                        data-target="#deleteModal"><i class="far fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4">No se ha encontrado Categorias registradas</td>
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
    <script src="{{ asset('/libs/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>

    <script>
        function editCategory(category) {

            $("#editProductFrm").attr('action', `/category/${category.id}`);
            $("#editProductFrm #name").val(category.name);

        }
    </script>


    @if (!$errors->isEmpty())
        @if ($errors->has('post'))
            <script>
                $(function() {
                    $('#createModal').modal('show');
                });
            </script>
        @else
            <script>
                $(function() {
                    $('#editModal').modal('show');
                });
            </script>
        @endif
    @endif


@endpush
