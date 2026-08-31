@extends('themes.backoffice.layouts.admin')

@section('title', 'Verificar comprobantes de transferencia')

@section('breadcrumbs')
<li>Verificar comprobantes de transferencia</li>
@endsection

@section('content')
<div class="section">

    @if(session('success'))
    <div class="card-panel green lighten-4">
        <i class="material-icons tiny">check_circle</i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="card-panel red lighten-4">
        <i class="material-icons tiny">error</i> {{ session('error') }}
    </div>
    @endif

    <div class="row valign-wrapper" style="margin-bottom:0">
        <div class="col s12">
            <h5 class="grey-text text-darken-2" style="margin:0 0 4px">
                <i class="material-icons left" style="font-size:1.5rem">fact_check</i>
                Comprobantes de transferencia pendientes de verificar
            </h5>
            <p class="grey-text" style="margin:0; font-size:.85rem">
                Reservas donde el cliente envió una foto del comprobante por WhatsApp.
                Revisa la imagen y aprueba o rechaza para confirmar (o pedir reenvío).
            </p>
        </div>
    </div>

    <div class="row" style="margin-top:20px">
        @if($pendientes->isEmpty())
        <div class="col s12">
            <div class="card-panel center grey-text" style="padding:32px 0">
                <i class="material-icons medium">check_circle_outline</i>
                <p style="margin:8px 0 0">No hay comprobantes pendientes de verificar.</p>
            </div>
        </div>
        @else
            @foreach($pendientes as $p)
            <div class="col s12 m6 l4">
                <div class="card-panel" style="padding:12px">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start">
                        <div>
                            <p style="margin:0; font-weight:700; font-size:14px">
                                {{ $p->cliente_nombre ?? 'Cliente sin nombre' }}
                            </p>
                            <p class="grey-text" style="margin:2px 0 0; font-size:12px">
                                {{ $p->numero_contacto }}
                            </p>
                        </div>
                        <span style="color:#f57f17; font-size:11px; font-weight:600; white-space:nowrap">
                            PENDIENTE
                        </span>
                    </div>

                    <p style="margin:8px 0 0; font-size:13px">
                        <i class="material-icons tiny" style="vertical-align:middle">event</i>
                        Reserva N°{{ $p->reserva_id }} — {{ $p->nombre_programa ?? 'Programa' }}
                        @if($p->fecha_visita)
                            — {{ \Carbon\Carbon::parse($p->fecha_visita)->format('d/m/Y') }}
                        @endif
                    </p>
                    <p style="margin:2px 0 8px; font-size:13px">
                        <i class="material-icons tiny" style="vertical-align:middle">payments</i>
                        Abono: <strong>${{ number_format($p->abono_programa ?? $p->total_pagar, 0, ',', '.') }}</strong>
                    </p>

                    @if($p->comprobante_monto || $p->comprobante_fecha || $p->comprobante_numero_operacion)
                    <div style="background:#f5f5f5; border-radius:4px; padding:8px 10px; margin-bottom:8px; font-size:12px">
                        <p style="margin:0 0 4px; font-weight:700; color:#555">
                            <i class="material-icons tiny" style="vertical-align:middle; font-size:14px">smart_toy</i>
                            Lectura automática del comprobante
                        </p>
                        <p style="margin:0">
                            Monto:
                            @if($p->comprobante_monto)
                                <strong>${{ number_format($p->comprobante_monto, 0, ',', '.') }}</strong>
                                @if($p->comprobante_tipo_detectado === 'total')
                                    <span style="background:#c8e6c9; color:#256029; padding:1px 6px; border-radius:3px; font-size:10px">TOTAL</span>
                                @elseif($p->comprobante_tipo_detectado === 'abono_50')
                                    <span style="background:#bbdefb; color:#0d47a1; padding:1px 6px; border-radius:3px; font-size:10px">ABONO 50%</span>
                                @elseif($p->comprobante_tipo_detectado === 'monto_insuficiente')
                                    <span style="background:#ffcdd2; color:#b71c1c; padding:1px 6px; border-radius:3px; font-size:10px">MONTO NO CALZA</span>
                                @endif
                            @else
                                <em>no legible</em>
                            @endif
                        </p>
                        <p style="margin:2px 0 0">
                            Fecha/hora: {{ $p->comprobante_fecha ? \Carbon\Carbon::parse($p->comprobante_fecha)->format('d/m/Y') : 'no legible' }}
                            {{ $p->comprobante_hora ?? '' }}
                        </p>
                        <p style="margin:2px 0 0">
                            N° operación: {{ $p->comprobante_numero_operacion ?? 'no legible' }}
                        </p>
                        <p style="margin:2px 0 0">
                            Origen: {{ $p->comprobante_nombre_origen ?? 'no legible' }}
                        </p>
                    </div>
                    @endif

                    @if($p->comprobante_alerta)
                    <div style="background:#fff3e0; border-left:3px solid #f57c00; border-radius:2px; padding:6px 10px; margin-bottom:8px; font-size:12px; color:#e65100">
                        <i class="material-icons tiny" style="vertical-align:middle; font-size:14px">warning</i>
                        {{ $p->comprobante_alerta }}
                    </div>
                    @endif

                    @if($p->comprobante_transferencia)
                    <a href="{{ route('backoffice.verificacion-pago.imagen', $p->venta_id) }}" target="_blank">
                        <img src="{{ route('backoffice.verificacion-pago.imagen', $p->venta_id) }}"
                             alt="Comprobante de transferencia"
                             style="width:100%; max-height:320px; object-fit:contain; border:1px solid #eee; border-radius:4px; background:#fafafa">
                    </a>
                    @else
                    <p class="grey-text center" style="padding:24px 0">Sin imagen adjunta.</p>
                    @endif

                    <div style="display:flex; gap:8px; margin-top:12px">
                        <form method="POST" action="{{ route('backoffice.verificacion-pago.aprobar', $p->venta_id) }}" style="flex:1">
                            @csrf
                            <button type="submit" class="btn waves-effect waves-light green darken-1" style="width:100%"
                                    onclick="return confirm('¿Aprobar este comprobante y confirmar la reserva N°{{ $p->reserva_id }}?')">
                                <i class="material-icons left">check</i> Aprobar
                            </button>
                        </form>
                        <form method="POST" action="{{ route('backoffice.verificacion-pago.rechazar', $p->venta_id) }}" style="flex:1">
                            @csrf
                            <button type="submit" class="btn waves-effect waves-light red darken-1" style="width:100%"
                                    onclick="return confirm('¿Rechazar este comprobante? Se le pedirá al cliente que lo reenvíe.')">
                                <i class="material-icons left">close</i> Rechazar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        @endif
    </div>

</div>
@endsection
