@extends('themes.backoffice.layouts.admin')
@section('title', 'Detalle de Egreso')
@section('head')
@endsection
@section('breadcrumbs')
<li><a href="{{route('backoffice.egreso.index')}}">Egresos Anuales</a></li>
<li><a href="{{route('backoffice.egreso.mes', [\Carbon\Carbon::parse($egreso->fecha_egreso)->year, \Carbon\Carbon::parse($egreso->fecha_egreso)->month])}}">{{ucfirst(\Carbon\Carbon::parse($egreso->fecha_egreso)->locale('es')->isoFormat('MMMM [de] YYYY'))}}</a></li>
<li>Detalle de Egreso</li>
@endsection
@section('dropdown_settings')
<li><a href="{{ route('backoffice.egreso.edit', $egreso->id) }}" class="grey-text text-darken-2">Editar este Egreso</a></li>
@endsection
@section('content')
<div class="section">
    <p class="caption">Detalle de Egreso <strong>#{{ $egreso->id }}</strong></p>
    <div class="divider"></div>
    <div class="row">
        <div class="col s12 m6">
            <div class="card-panel">
                <h5>Datos generales</h5>
                <table class="striped">
                    <tbody>
                        <tr><td><strong>Proveedor</strong></td><td>{{ $egreso->proveedor->nombre ?? '-' }}</td></tr>
                        <tr><td><strong>RUT Proveedor</strong></td><td>{{ $egreso->proveedor->rut ?? '-' }}</td></tr>
                        <tr><td><strong>Categoría</strong></td><td>{{ $egreso->categoria->nombre ?? '-' }}</td></tr>
                        <tr><td><strong>Subcategoría</strong></td><td>{{ $egreso->subcategoria->nombre ?? '-' }}</td></tr>
                        <tr><td><strong>Tipo Documento</strong></td><td>{{ $egreso->tipo_documento->nombre ?? '-' }}</td></tr>
                        <tr><td><strong>N° Documento</strong></td><td>{{ $egreso->numero_documento ?? '-' }}</td></tr>
                        <tr><td><strong>Fecha Egreso</strong></td><td>{{ \Carbon\Carbon::parse($egreso->fecha_egreso)->format('d-m-Y') }}</td></tr>
                        <tr><td><strong>Fuente</strong></td><td>{{ $egreso->fuente ?? '-' }}</td></tr>
                        <tr><td><strong>Estado</strong></td><td>{{ $egreso->estado ?? '-' }}</td></tr>
                        <tr><td><strong>Periodo SII</strong></td><td>{{ $egreso->periodo_sii ?? '-' }}</td></tr>
                        <tr>
                            <td><strong>Reconciliado</strong></td>
                            <td>
                                @if($egreso->reconciliado_con_id)
                                    <span class="chip" style="background:#e8f5e9;color:#2e7d32">
                                        <i class="material-icons tiny" style="vertical-align:middle;margin-right:4px">done</i>
                                        Sí, con egreso #{{ $egreso->reconciliado_con_id }}
                                    </span>
                                @else
                                    <span class="grey-text">No</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col s12 m6">
            <div class="card-panel">
                <h5>Montos</h5>
                <table class="striped">
                    <tbody>
                        <tr><td><strong>Neto</strong></td><td>${{ number_format($egreso->neto ?? 0, 0, ',', '.') }}</td></tr>
                        <tr><td><strong>IVA</strong></td><td>${{ number_format($egreso->iva ?? 0, 0, ',', '.') }}</td></tr>
                        <tr><td><strong>Total</strong></td><td><strong>${{ number_format($egreso->total ?? 0, 0, ',', '.') }}</strong></td></tr>
                    </tbody>
                </table>
                @if($egreso->observaciones)
                <h5 style="margin-top:30px">Observaciones</h5>
                <p>{{ $egreso->observaciones }}</p>
                @endif
            </div>
        </div>
    </div>
    @if($egreso->pagos && $egreso->pagos->count())
    <div class="row">
        <div class="col s12">
            <div class="card-panel">
                <h5>Pagos registrados (histórico, tabla legacy)</h5>
                <table class="striped responsive-table">
                    <thead>
                        <tr>
                            <th>Fecha Pago</th>
                            <th>Folio</th>
                            <th>Neto</th>
                            <th>IVA</th>
                            <th>Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($egreso->pagos as $pago)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d-m-Y') }}</td>
                            <td>{{ $pago->folio ?? '-' }}</td>
                            <td>${{ number_format($pago->neto ?? 0, 0, ',', '.') }}</td>
                            <td>${{ number_format($pago->iva ?? 0, 0, ',', '.') }}</td>
                            <td>${{ number_format($pago->monto ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    <div class="section">
        <a href="{{ route('backoffice.egreso.mes', [\Carbon\Carbon::parse($egreso->fecha_egreso)->year, \Carbon\Carbon::parse($egreso->fecha_egreso)->month]) }}" class="btn waves-effect grey">
            <i class="material-icons left">arrow_back</i> Volver al mes
        </a>
        <a href="{{ route('backoffice.egreso.edit', $egreso->id) }}" class="btn waves-effect purple">
            <i class="material-icons left">edit</i> Editar
        </a>
    </div>
</div>
@endsection
