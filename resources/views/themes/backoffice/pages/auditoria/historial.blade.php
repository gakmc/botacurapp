@extends('themes.backoffice.layouts.admin')

@section('title','Historial')

@section('head')
@include('themes.backoffice.pages.auditoria.partials.estilos')
@endsection

@section('breadcrumbs')
<li><a href="{{ route('backoffice.auditoria.index') }}">Auditoría</a></li>
<li>Historial</li>
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Historial: {{ $titulo }}</strong></p>
    <div class="divider"></div>
    <div class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">
                    @php
                        $alcance = [
                            'reserva' => ' (venta, pedidos, servicios extra, menús, masajes, ubicaciones, pagos y cierre)',
                            'usuario' => ' (accesos, roles, permisos, sueldos y datos de la cuenta)',
                        ];
                    @endphp
                    <p class="grey-text">
                        Todas las acciones sobre este registro y sus registros relacionados{{ $alcance[$tipo] ?? '' }}.
                        {{ $auditorias->total() }} registro(s).
                        @if ($tipo === 'usuario')
                            <br>Las acciones que esta persona <em>realizó</em> están en
                            <a href="{{ route('backoffice.auditoria.index', ['user_id' => $id]) }}">el listado filtrado por usuario</a>.
                        @endif
                    </p>

                    @include('themes.backoffice.pages.auditoria.partials.tabla')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
@endsection
