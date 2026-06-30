@extends('themes.backoffice.layouts.admin')

@section('title', 'Honorarios BTE – ' . $nombreMes)

@section('breadcrumbs')
<li><a href="{{ route('backoffice.egreso.index') }}">Egresos</a></li>
<li>Honorarios BTE</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.honorarios.resumen') }}" class="grey-text text-darken-2">Resumen por Emisor</a></li>
<li><a href="{{ route('backoffice.sii.index') }}" class="grey-text text-darken-2">SII RCV</a></li>
@endsection

@section('content')
<div class="section">

    {{-- Alertas --}}
    @if (!$credencialesOk)
    <div class="card-panel red lighten-4">
        <i class="material-icons tiny">warning</i>
        Credenciales SII incompletas. Revisa <code>SII_RUT_EMPRESA</code> y <code>SII_CLAVE_TRIBUTARIA</code> en el .env.
    </div>
    @endif

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

    {{-- Header + controles período --}}
    <div class="row" style="margin-bottom:0">
        <div class="col s12 m8">
            <h5 class="grey-text text-darken-2" style="margin:0 0 4px">
                <i class="material-icons left" style="font-size:1.5rem">receipt</i>
                Honorarios BTE Recibidas — {{ $nombreMes }}
            </h5>
            <p class="grey-text" style="margin:0;font-size:.85rem">
                Boletas de Prestación de Servicios de Terceros Electrónicas recibidas como empresa receptora.
                @if($ultimaSync)
                    <span class="grey-text">Última sync: {{ \Carbon\Carbon::parse($ultimaSync)->format('d/m/Y H:i') }}</span>
                @endif
            </p>
        </div>
        <div class="col s12 m4" style="text-align:right;padding-top:4px">
            {{-- Selector de período --}}
            <form method="GET" action="{{ route('backoffice.honorarios.index') }}" style="display:inline-flex;gap:8px;align-items:center">
                <select name="mes" class="browser-default" style="width:120px;border:1px solid #ccc;border-radius:4px;padding:4px 8px">
                    @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $m)
                    <option value="{{ $i+1 }}" {{ $mes == $i+1 ? 'selected' : '' }}>{{ $m }}</option>
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

    {{-- Tarjetas resumen --}}
    <div class="row" style="margin-top:16px;margin-bottom:8px">
        <div class="col s6 m3">
            <div class="card-panel center-align" style="padding:12px 8px;margin:4px 0">
                <span class="grey-text" style="font-size:.75rem;display:block">BTE del período</span>
                <span style="font-size:1.8rem;font-weight:700;color:#455a64">{{ $resumen['total_bte'] }}</span>
            </div>
        </div>
        <div class="col s6 m3">
            <div class="card-panel center-align" style="padding:12px 8px;margin:4px 0">
                <span class="grey-text" style="font-size:.75rem;display:block">Honorarios Brutos</span>
                <span style="font-size:1.2rem;font-weight:700;color:#d32f2f">${{ number_format($resumen['monto_bruto'],0,',','.') }}</span>
            </div>
        </div>
        <div class="col s6 m3">
            <div class="card-panel center-align" style="padding:12px 8px;margin:4px 0">
                <span class="grey-text" style="font-size:.75rem;display:block">Retención (15.25%)</span>
                <span style="font-size:1.2rem;font-weight:700;color:#f57c00">${{ number_format($resumen['monto_retenido'],0,',','.') }}</span>
            </div>
        </div>
        <div class="col s6 m3">
            <div class="card-panel center-align" style="padding:12px 8px;margin:4px 0">
                <span class="grey-text" style="font-size:.75rem;display:block">Pagado al Emisor</span>
                <span style="font-size:1.2rem;font-weight:700;color:#388e3c">${{ number_format($resumen['monto_pagado'],0,',','.') }}</span>
            </div>
        </div>
    </div>

    {{-- Botón sincronizar --}}
    @if($credencialesOk)
    <div style="margin-bottom:16px">
        <form method="POST" action="{{ route('backoffice.honorarios.sincronizar') }}" style="display:inline">
            @csrf
            <input type="hidden" name="anio" value="{{ $anio }}">
            <input type="hidden" name="mes" value="{{ $mes }}">
            <button type="submit" class="btn waves-effect waves-light teal darken-1" id="btn-sync">
                <i class="material-icons left">sync</i>
                Sincronizar desde SII
            </button>
        </form>
        <small class="grey-text" style="margin-left:8px">
            Descarga las BTE directamente del portal zeus.sii.cl
        </small>
    </div>
    @endif

    {{-- Tabla BTE del período --}}
    <div class="card" style="border-radius:8px;overflow:hidden">
        <div class="card-content" style="padding:0">
            @if($honorarios->isEmpty())
            <div style="padding:40px;text-align:center;color:#9e9e9e">
                <i class="material-icons" style="font-size:3rem;display:block;margin-bottom:8px">inbox</i>
                No hay BTE registradas para {{ $nombreMes }}.
                @if($credencialesOk)
                <br><small>Usa el botón "Sincronizar desde SII" para descargar.</small>
                @endif
            </div>
            @else
            <table class="striped responsive-table" style="margin:0">
                <thead>
                    <tr style="background:#eceff1">
                        <th>N° BTE</th>
                        <th>Estado</th>
                        <th>Fecha Emisión</th>
                        <th>RUT Emisor</th>
                        <th>Nombre Emisor</th>
                        <th>Fecha Pago</th>
                        <th class="right-align">Bruto</th>
                        <th class="right-align">Retención</th>
                        <th class="right-align">Pagado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($honorarios as $h)
                    <tr class="{{ $h->estado === 'Anulada' ? 'red lighten-5' : '' }}">
                        <td style="font-family:monospace;white-space:nowrap">{{ $h->folio }}</td>
                        <td>
                            @if($h->estado === 'Anulada')
                                <span class="red-text"><i class="material-icons tiny">cancel</i> Anulada</span>
                            @else
                                <span class="green-text"><i class="material-icons tiny">check_circle</i> {{ $h->estado ?: 'Vigente' }}</span>
                            @endif
                        </td>
                        <td>{{ $h->fecha_emision ? $h->fecha_emision->format('d/m/Y') : '—' }}</td>
                        <td style="font-family:monospace">{{ $h->rut_formateado }}</td>
                        <td>{{ $h->nombre_emisor ?: '—' }}</td>
                        <td>{{ $h->fecha_pago ? $h->fecha_pago->format('d/m/Y') : '—' }}</td>
                        <td class="right-align {{ $h->estado === 'Anulada' ? 'grey-text' : '' }}">
                            ${{ number_format($h->monto_bruto, 0, ',', '.') }}
                        </td>
                        <td class="right-align orange-text text-darken-2">
                            ${{ number_format($h->monto_retenido, 0, ',', '.') }}
                        </td>
                        <td class="right-align green-text text-darken-2">
                            ${{ number_format($h->monto_pagado, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#f5f5f5;font-weight:700">
                        <td colspan="6">TOTALES (vigentes)</td>
                        <td class="right-align red-text">${{ number_format($resumen['monto_bruto'],0,',','.') }}</td>
                        <td class="right-align orange-text text-darken-2">${{ number_format($resumen['monto_retenido'],0,',','.') }}</td>
                        <td class="right-align green-text text-darken-2">${{ number_format($resumen['monto_pagado'],0,',','.') }}</td>
                    </tr>
                </tfoot>
            </table>
            @endif
        </div>
    </div>

    {{-- Resumen anual (tabla colapsable) --}}
    @if($resumenAnual->isNotEmpty())
    <div style="margin-top:24px">
        <h6 class="grey-text text-darken-2">Resumen {{ $anio }}</h6>
        <table class="striped" style="font-size:.9rem">
            <thead>
                <tr style="background:#eceff1">
                    <th>Mes</th>
                    <th class="center-align">BTE</th>
                    <th class="right-align">Bruto</th>
                    <th class="right-align">Retención</th>
                    <th class="right-align">Pagado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resumenAnual as $r)
                @php
                    $mesNum  = (int) substr($r->periodo, 4, 2);
                    $meses   = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                    $esActual = $r->periodo === $periodo;
                @endphp
                <tr class="{{ $esActual ? 'blue lighten-5' : '' }}">
                    <td>
                        <a href="{{ route('backoffice.honorarios.index', ['anio' => $anio, 'mes' => $mesNum]) }}"
                           class="{{ $esActual ? 'blue-text font-weight-bold' : '' }}">
                            {{ $meses[$mesNum] ?? $r->periodo }}
                        </a>
                    </td>
                    <td class="center-align">{{ $r->cantidad }}</td>
                    <td class="right-align">${{ number_format($r->total_bruto, 0, ',', '.') }}</td>
                    <td class="right-align orange-text">${{ number_format($r->total_retenido, 0, ',', '.') }}</td>
                    <td class="right-align green-text">${{ number_format($r->total_pagado, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>
@endsection
