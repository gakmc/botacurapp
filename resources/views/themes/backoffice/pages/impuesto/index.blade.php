@extends('themes.backoffice.layouts.admin')

@section('title', 'F29 Estimado – ' . $nombreMes)

@section('breadcrumbs')
<li><a href="{{ route('backoffice.sii.index') }}">SII</a></li>
<li>F29 Estimado</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.honorarios.index') }}" class="grey-text text-darken-2">Honorarios BTE</a></li>
<li><a href="{{ route('backoffice.sii.resumen') }}" class="grey-text text-darken-2">RCV Compras</a></li>
@endsection

@section('content')
<div class="section">

    {{-- Alertas --}}
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

    {{-- Header --}}
    <div class="row valign-wrapper" style="margin-bottom:0">
        <div class="col s12 m7">
            <h5 class="grey-text text-darken-2" style="margin:0 0 4px">
                <i class="material-icons left" style="font-size:1.5rem">account_balance</i>
                F29 Estimado — {{ ucfirst($nombreMes) }}
            </h5>
            <p class="grey-text" style="margin:0;font-size:.85rem">
                Empresa exenta de IVA. F29 = PPM (0.25% × ventas SII) + Retenciones BTE.
                @if($resumen && $resumen->ultima_sincronizacion)
                    <span>Última sync: {{ $resumen->ultima_sincronizacion->format('d/m/Y H:i') }}</span>
                @endif
            </p>
        </div>
        <div class="col s12 m5" style="text-align:right">
            {{-- Selector período --}}
            <form method="GET" action="{{ route('backoffice.impuesto.index') }}" style="display:inline-flex;gap:8px;align-items:center">
                <select name="mes" class="browser-default" style="width:120px;border:1px solid #ccc;border-radius:4px;padding:4px 8px">
                    @foreach($mesesNombres as $n => $nombre)
                    <option value="{{ $n }}" {{ $mes == $n ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
                <select name="anio" class="browser-default" style="width:80px;border:1px solid #ccc;border-radius:4px;padding:4px 8px">
                    @foreach(range(date('Y'), 2020) as $a)
                    <option value="{{ $a }}" {{ $anio == $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-flat waves-effect">
                    <i class="material-icons">search</i>
                </button>
            </form>
        </div>
    </div>

    {{-- Botón sincronizar ventas SII --}}
    @if($credencialesOk)
    <div style="margin:16px 0 8px">
        <form method="POST" action="{{ route('backoffice.impuesto.sincronizar') }}" style="display:inline">
            @csrf
            <input type="hidden" name="anio" value="{{ $anio }}">
            <input type="hidden" name="mes"  value="{{ $mes }}">
            <button type="submit" class="btn waves-effect waves-light teal darken-1">
                <i class="material-icons left">sync</i>
                Sincronizar Ventas SII
            </button>
        </form>
        <small class="grey-text" style="margin-left:8px">
            Consulta el RCV de ventas para calcular la base del PPM
        </small>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════
         CARDS RESUMEN F29
    ═══════════════════════════════════════════════════════ --}}
    <div class="row" style="margin-top:16px;margin-bottom:0">

        {{-- Ventas SII (base PPM) --}}
        <div class="col s6 m3">
            <div class="card-panel" style="padding:14px 12px;margin:4px 0;border-left:4px solid #039B7B">
                <span class="grey-text" style="font-size:.72rem;display:block;text-transform:uppercase;letter-spacing:.5px">Ventas SII</span>
                <span style="font-size:1.3rem;font-weight:700;color:#039B7B">
                    ${{ number_format($ventasTotal, 0, ',', '.') }}
                </span>
                <span class="grey-text" style="font-size:.75rem;display:block">
                    {{ $ventasCant }} doc(s) · base PPM
                    @if(!$resumen)<br><small class="orange-text">Sin sincronizar</small>@endif
                </span>
            </div>
        </div>

        {{-- PPM --}}
        <div class="col s6 m3">
            <div class="card-panel" style="padding:14px 12px;margin:4px 0;border-left:4px solid #1565C0">
                <span class="grey-text" style="font-size:.72rem;display:block;text-transform:uppercase;letter-spacing:.5px">PPM (0.25%)</span>
                <span style="font-size:1.3rem;font-weight:700;color:#1565C0">
                    ${{ number_format($ppm, 0, ',', '.') }}
                </span>
                <span class="grey-text" style="font-size:.75rem;display:block">Línea 563 F29</span>
            </div>
        </div>

        {{-- Retenciones BTE --}}
        <div class="col s6 m3">
            <div class="card-panel" style="padding:14px 12px;margin:4px 0;border-left:4px solid #E65100">
                <span class="grey-text" style="font-size:.72rem;display:block;text-transform:uppercase;letter-spacing:.5px">Retenciones BTE</span>
                <span style="font-size:1.3rem;font-weight:700;color:#E65100">
                    ${{ number_format($retencionBte, 0, ',', '.') }}
                </span>
                <span class="grey-text" style="font-size:.75rem;display:block">
                    {{ $bteCantidad }} boleta(s) · Línea 151
                </span>
            </div>
        </div>

        {{-- Total F29 --}}
        <div class="col s6 m3">
            <div class="card-panel" style="padding:14px 12px;margin:4px 0;border-left:4px solid #B71C1C;background:#fff8f8">
                <span class="grey-text" style="font-size:.72rem;display:block;text-transform:uppercase;letter-spacing:.5px">Total F29 Estimado</span>
                <span style="font-size:1.5rem;font-weight:700;color:#B71C1C">
                    ${{ number_format($totalF29, 0, ',', '.') }}
                </span>
                <span class="grey-text" style="font-size:.75rem;display:block">
                    Vence 12 de {{ $mesesNombres[$mes == 12 ? 1 : $mes + 1] ?? 'próx. mes' }}
                </span>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         DESGLOSE LÍNEAS F29
    ═══════════════════════════════════════════════════════ --}}
    <div class="row" style="margin-top:8px">
        <div class="col s12 m6">
            <div class="card" style="border-radius:8px">
                <div class="card-content" style="padding:16px 20px">
                    <p class="grey-text text-darken-2" style="font-weight:600;margin:0 0 12px;font-size:.95rem">
                        <i class="material-icons tiny">description</i> Líneas F29
                    </p>

                    <table style="width:100%;font-size:.9rem">
                        <tbody>
                            <tr style="border-bottom:1px solid #f0f0f0">
                                <td class="grey-text" style="padding:6px 0">Ventas exentas / netas SII</td>
                                <td style="text-align:right">
                                    @if($ventasExento > 0)
                                        ${{ number_format($ventasExento, 0, ',', '.') }}
                                    @elseif($ventasTotal > 0)
                                        ${{ number_format($ventasTotal, 0, ',', '.') }}
                                    @else
                                        <span class="grey-text">Sin datos</span>
                                    @endif
                                </td>
                            </tr>
                            <tr style="border-bottom:1px solid #f0f0f0">
                                <td class="grey-text" style="padding:6px 0">
                                    IVA Crédito Fiscal (compras SII)
                                </td>
                                <td style="text-align:right;color:#388E3C">
                                    @if($creditoFiscal > 0)
                                        ${{ number_format($creditoFiscal, 0, ',', '.') }}
                                        <small class="grey-text">(remanente)</small>
                                    @else
                                        <span class="grey-text">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr style="border-bottom:1px solid #f0f0f0">
                                <td style="padding:6px 0">
                                    <strong>Línea 563</strong> — PPM (0.25%)
                                </td>
                                <td style="text-align:right;color:#1565C0;font-weight:700">
                                    ${{ number_format($ppm, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr style="border-bottom:1px solid #f0f0f0">
                                <td style="padding:6px 0">
                                    <strong>Línea 151</strong> — Retención honorarios
                                </td>
                                <td style="text-align:right;color:#E65100;font-weight:700">
                                    ${{ number_format($retencionBte, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr style="background:#fff8f8">
                                <td style="padding:8px 0;font-weight:700;font-size:1rem">
                                    <strong>Línea 595</strong> — Total a pagar
                                </td>
                                <td style="text-align:right;color:#B71C1C;font-weight:700;font-size:1rem">
                                    ${{ number_format($totalF29, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="grey-text" style="font-size:.75rem;margin:10px 0 0">
                        * IVA Crédito no genera reembolso al ser empresa exenta. Se registra como costo.
                    </p>
                </div>
            </div>
        </div>

        {{-- Proyección mes actual --}}
        @if($esActual && $proyeccion)
        <div class="col s12 m6">
            <div class="card" style="border-radius:8px">
                <div class="card-content" style="padding:16px 20px">
                    <p class="grey-text text-darken-2" style="font-weight:600;margin:0 0 12px;font-size:.95rem">
                        <i class="material-icons tiny">trending_up</i> Proyección (promedio 3 meses)
                    </p>

                    @if($proyeccion['promedio_ventas'] > 0)
                    <table style="width:100%;font-size:.9rem">
                        <tbody>
                            <tr style="border-bottom:1px solid #f0f0f0">
                                <td class="grey-text" style="padding:6px 0">Ventas promedio mensual</td>
                                <td style="text-align:right">${{ number_format($proyeccion['promedio_ventas'], 0, ',', '.') }}</td>
                            </tr>
                            <tr style="border-bottom:1px solid #f0f0f0">
                                <td style="padding:6px 0">PPM proyectado</td>
                                <td style="text-align:right;color:#1565C0;font-weight:600">
                                    ${{ number_format($proyeccion['ppm_proyectado'], 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr style="border-bottom:1px solid #f0f0f0">
                                <td style="padding:6px 0">Retenciones BTE (acumulado)</td>
                                <td style="text-align:right;color:#E65100;font-weight:600">
                                    ${{ number_format($retencionBte, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr style="background:#f3f8ff">
                                <td style="padding:8px 0;font-weight:700">F29 proyectado</td>
                                <td style="text-align:right;color:#1A237E;font-weight:700;font-size:1.05rem">
                                    ${{ number_format($proyeccion['total_proyectado'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    @else
                    <p class="grey-text center-align" style="padding:20px 0">
                        <i class="material-icons" style="display:block;font-size:2rem;margin-bottom:8px">history</i>
                        No hay suficiente historial para proyectar.<br>
                        <small>Sincroniza los meses anteriores desde esta vista.</small>
                    </p>
                    @endif

                    @if(!$proyeccion['sincronizado'])
                    <div class="card-panel orange lighten-4" style="padding:8px 12px;margin:10px 0 0;font-size:.82rem">
                        <i class="material-icons tiny">warning</i>
                        Ventas de este mes aún no sincronizadas. Presiona "Sincronizar Ventas SII".
                    </div>
                    @else
                    <p class="grey-text" style="font-size:.75rem;margin:10px 0 0">
                        Sync: {{ $proyeccion['ultima_sync'] ? $proyeccion['ultima_sync']->format('d/m/Y H:i') : '—' }}
                    </p>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════
         BTE SEMANA A SEMANA (solo mes actual)
    ═══════════════════════════════════════════════════════ --}}
    @if($esActual && count($bteSemanales) > 0)
    <div class="card" style="border-radius:8px;margin-top:4px">
        <div class="card-content" style="padding:16px 20px">
            <p class="grey-text text-darken-2" style="font-weight:600;margin:0 0 12px;font-size:.95rem">
                <i class="material-icons tiny">date_range</i>
                Retenciones BTE — Acumulado semanal ({{ ucfirst($nombreMes) }})
            </p>
            <div style="overflow-x:auto">
            <table class="striped" style="font-size:.88rem;min-width:400px">
                <thead>
                    <tr style="background:#eceff1">
                        <th>Semana</th>
                        <th class="right-align">Retención semana</th>
                        <th class="right-align">Acumulado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bteSemanales as $sem)
                    <tr style="
                        {{ $sem['activa'] ? 'background:#e8f5e9;font-weight:600' : '' }}
                        {{ !$sem['pasada'] && !$sem['activa'] ? 'color:#9e9e9e' : '' }}
                    ">
                        <td>
                            {{ $sem['label'] }}
                            @if($sem['activa'])
                                <span class="green-text" style="font-size:.75rem;margin-left:6px">▶ actual</span>
                            @endif
                        </td>
                        <td class="right-align {{ $sem['retencion'] > 0 ? 'orange-text text-darken-2' : 'grey-text' }}">
                            @if($sem['retencion'] > 0)
                                ${{ number_format($sem['retencion'], 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="right-align" style="font-weight:{{ $sem['activa'] ? '700' : '400' }}">
                            ${{ number_format($sem['acumulado'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════
         RESUMEN ANUAL
    ═══════════════════════════════════════════════════════ --}}
    <div style="margin-top:24px">
        <h6 class="grey-text text-darken-2" style="margin-bottom:8px">Resumen F29 — {{ $anio }}</h6>
        <div style="overflow-x:auto">
        <table class="striped" style="font-size:.88rem;min-width:500px">
            <thead>
                <tr style="background:#eceff1">
                    <th>Mes</th>
                    <th class="right-align">Ventas SII</th>
                    <th class="right-align">PPM (0.25%)</th>
                    <th class="right-align">Retenc. BTE</th>
                    <th class="right-align">Total F29</th>
                    <th class="center-align">Estado</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalAnualVentas = 0;
                    $totalAnualPpm    = 0;
                    $totalAnualRet    = 0;
                    $totalAnualF29    = 0;
                @endphp
                @foreach($resumenAnual as $m => $datos)
                @php $esEsteMes = ($m == $mes && $anio == now()->year && $m == now()->month); @endphp
                <tr class="{{ $esEsteMes ? 'blue lighten-5' : '' }}" style="{{ $datos === null ? 'color:#bdbdbd' : '' }}">
                    <td>
                        <a href="{{ route('backoffice.impuesto.index', ['anio' => $anio, 'mes' => $m]) }}"
                           class="{{ $esEsteMes ? 'blue-text font-weight-bold' : '' }}">
                            {{ $mesesNombres[$m] ?? $m }}
                        </a>
                    </td>
                    @if($datos === null)
                    <td class="right-align grey-text" colspan="4">—</td>
                    <td class="center-align grey-text" style="font-size:.75rem">Futuro</td>
                    @else
                    @php
                        $totalAnualVentas += $datos['ventas'];
                        $totalAnualPpm    += $datos['ppm'];
                        $totalAnualRet    += $datos['retenciones'];
                        $totalAnualF29    += $datos['total'];
                    @endphp
                    <td class="right-align">
                        @if($datos['ventas'] > 0)
                            ${{ number_format($datos['ventas'], 0, ',', '.') }}
                        @else
                            <span class="orange-text text-darken-1" style="font-size:.75rem">Sin sync</span>
                        @endif
                    </td>
                    <td class="right-align blue-text text-darken-2">
                        ${{ number_format($datos['ppm'], 0, ',', '.') }}
                    </td>
                    <td class="right-align orange-text text-darken-2">
                        ${{ number_format($datos['retenciones'], 0, ',', '.') }}
                    </td>
                    <td class="right-align red-text text-darken-2" style="font-weight:600">
                        ${{ number_format($datos['total'], 0, ',', '.') }}
                    </td>
                    <td class="center-align">
                        @if($datos['sincronizado'])
                            <span class="green-text" style="font-size:.75rem">
                                <i class="material-icons tiny">check_circle</i> Sync
                            </span>
                        @endif
                        @if($credencialesOk && $datos !== null)
                            <form method="POST" action="{{ route('backoffice.impuesto.sincronizar') }}" style="display:inline" onsubmit="this.querySelector('button').disabled=true">
                                @csrf
                                <input type="hidden" name="anio" value="{{ $anio }}">
                                <input type="hidden" name="mes" value="{{ $m }}">
                                <button type="submit" class="btn-flat btn-small waves-effect teal-text" style="padding:0 6px;height:24px;line-height:24px" title="Sincronizar {{ $mesesNombres[$m] ?? $m }}">
                                    <i class="material-icons" style="font-size:1rem;line-height:24px">sync</i>
                                </button>
                            </form>
                        @elseif(!$datos['sincronizado'])
                            <span class="orange-text" style="font-size:.75rem">
                                <i class="material-icons tiny">sync_problem</i> Pend.
                            </span>
                        @endif
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f0faf7;font-weight:700">
                    <td>TOTAL {{ $anio }}</td>
                    <td class="right-align">${{ number_format($totalAnualVentas, 0, ',', '.') }}</td>
                    <td class="right-align blue-text text-darken-2">${{ number_format($totalAnualPpm, 0, ',', '.') }}</td>
                    <td class="right-align orange-text text-darken-2">${{ number_format($totalAnualRet, 0, ',', '.') }}</td>
                    <td class="right-align red-text text-darken-2">${{ number_format($totalAnualF29, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>

</div>
@endsection
