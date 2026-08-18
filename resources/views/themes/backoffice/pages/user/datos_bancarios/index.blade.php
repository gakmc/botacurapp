@extends('themes.backoffice.layouts.admin')

@section('title','Datos Bancarios')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.datos-bancarios.index')}}">Datos Bancarios</a></li>
@endsection

@section('content')

<div class="section">
    <p class="caption"><strong>Datos Bancarios y Boletas de Honorarios</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">

                    <table class="responsive-table highlight">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>RUT</th>
                                <th>Boletea</th>
                                <th>Banco</th>
                                <th>Tipo cuenta</th>
                                <th>N° cuenta</th>
                                <th>Correo personal</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($usuarios as $usuario)
                            <tr>
                                <td>{{ $usuario->name }}</td>
                                <td>{{ $usuario->rut ?: '—' }}</td>
                                <td>
                                    @if($usuario->boletea)
                                        <i class="material-icons green-text" title="Boletea">check_circle</i>
                                    @else
                                        <i class="material-icons grey-text" title="No boletea">remove_circle_outline</i>
                                    @endif
                                </td>
                                <td>{{ $usuario->banco ?: '—' }}</td>
                                <td>{{ $usuario->tipo_cuenta_bancaria ?: '—' }}</td>
                                <td>{{ $usuario->numero_cuenta_bancaria ?: '—' }}</td>
                                <td>{{ $usuario->correo_personal ?: '—' }}</td>
                                <td>
                                    <a class="btn-small waves-effect waves-light" href="{{ route('backoffice.datos-bancarios.edit', $usuario) }}">
                                        <i class="material-icons">edit</i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8">No hay usuarios visibles.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
@endsection
