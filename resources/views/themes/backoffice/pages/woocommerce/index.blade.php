@extends('themes.backoffice.layouts.admin')

@section('title', 'Órdenes Botacura.cl')

@section('head')
@endsection

@section('breadcrumbs')
<li>Órdenes Botacura.cl</li>
@endsection

@section('dropdown_settings')
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Reservas compradas desde Botacura.cl</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">

                    <table class="responsive-table">
                        <thead>
                            <tr>
                                <th># Orden</th>
                                <th>Cliente</th>
                                <th>Programa</th>
                                <th>Cantidad de asistentes</th>
                                <th>Fecha Visita</th>
                                <th>Total Pagado</th>
                                <th>Método de Pago</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ventas as $venta)

                                <tr>
                                    <td>{{ $venta->wc_order_id }}</td>
                                    <td>
                                        {{ $venta->billing_first_name }} {{ $venta->billing_last_name }}<br>
                                        <small class="grey-text">{{ $venta->billing_email }}</small><br>
                                        <small class="grey-text">{{ $venta->billing_phone }}</small>
                                    </td>
                                    <td>{{ optional($venta->programa)->nombre_programa ?? '—' }}</td>
                                    <td>{{ $venta->payload_raw["line_items"][0]["quantity"] ?? '—' }}</td>
                                    <td>
                                        {{ $venta->fecha_visita_wc ? $venta->fecha_visita_wc->locale('es')->isoFormat('D [de] MMMM [de] YYYY') : '—' }}
                                    </td>
                                    <td>${{ number_format($venta->total, 0, ',', '.') }} {{ $venta->currency }}</td>
                                    <td>{{ $venta->payment_method }}</td>
                                    <td>
                                        @if ($venta->status == "completed")
                                            <span class="new badge green" data-badge-caption="">Pagado</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php $clienteExistente = $clientes->get(strtolower(trim($venta->billing_email ?? ''))); @endphp
                                        @if ($clienteExistente)
                                            <a href="{{ route('backoffice.reserva.create', $clienteExistente->id) }}"
                                               class="btn-small waves-effect waves-light green darken-1"
                                               title="Crear reserva para cliente existente">
                                                <i class="material-icons left">event_available</i> Reserva
                                            </a>
                                        @else
                                            <a href="{{ route('backoffice.cliente.create', [
                                                    'nombre'   => trim($venta->billing_first_name . ' ' . $venta->billing_last_name),
                                                    'correo'   => $venta->billing_email,
                                                    'whatsapp' => $venta->billing_phone,
                                                ]) }}"
                                               class="btn-small waves-effect waves-light orange darken-1"
                                               title="Crear cliente y luego reserva">
                                                <i class="material-icons left">person_add</i> Cliente
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="center-align grey-text">No hay órdenes registradas.</td>
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
