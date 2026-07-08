@extends('layouts.app')

@section('title', 'Gestionar Departamentos')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-sitemap"></i> Gestionar Departamentos</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="/admin/departments/create" class="btn btn-success">
            <i class="fas fa-plus"></i> Nuevo Departamento
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Usuarios</th>
                        <th>Tickets</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($departments as $dept)
                        <tr>
                            <td><strong>{{ $dept->name }}</strong></td>
                            <td>{{ $dept->description }}</td>
                            <td><span class="badge bg-info">{{ $dept->users_count }}</span></td>
                            <td><span class="badge bg-warning">{{ $dept->tickets_count }}</span></td>
                            <td>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                        data-bs-target="#editModal{{ $dept->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $departments->links() }}
        </div>
    </div>
</div>
@endsection
