@extends('themes.backoffice.layouts.admin')

@section('title', 'Honorarios BTE — ' . $nombreMes)

@section('breadcrumbs')
<li><a href="{{ route('backoffice.sii.index') }}">SII</a></li>
<li>Honorarios BTE</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.honorarios.resumen') }}" class="grey-text text-darken-2">Resumen anual</a></li>
<li><a href="{{ route('backoffice.sii.resumen') }}" class="grey-text text-darken-2">RCV Compras</a></li>
<li><a href="{{ route('backoffice.impuesto.index') }}" class="grey-text text-darken-2">F29 Estimado</a></li>
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

    {{-- Header --}}
    <div class="row valign-wrapper" style="margin-bottom:0">
        <div class="col s12 m7">
            <h5 class="grey-text text-darken-2" style="margin:0 0 4px">
                <i class="material-icons left" style="font-size:1.5rem">receipt</i>
                Honorarios BTE — {{ $nombreMes }}
            </h5>
            <p class="grey-text" style="margin:0; font-size:.85rem">
                Boletas de prestación de servicios de terceros recibidas.
                @if($ultimaSync)
                    Última sync: {{ \Carbon\Carbon::parse($ultimaSync)->format('d/m/Y H:i') }}
                @else
                    Sin sincronizar.
                @endif
            </p>
        </div>
        <div class="col s12 m5" style="text-align:right">
            <form method="GET" action="{{ route('backoffice.honorarios.index') }}"
                  style="display:inline-flex; gap:8px; align-items:center;">
                <select name="mes" class="browser-default"
                        style="width:130px; border:1px solid #ccc; border-radius:4px; padding:4px 8px">
                    @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $nombre)
                    <option value="{{ $i + 1 }}" {{ $mes == ($i+1) ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
                <select name="anio" class="browser-default"
                        style="width:80px; border:1px solid #ccc; border-radius:4px; padding:4px 8px">
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

    {{-- Sincronizar --}}
    @if($credencialesOk)
    <div style="margin:16px 0 8px">
        <form method="POST" action="{{ route('backoffice.honorarios.sincronizar') }}" style="display:inline">
            @csrf
            <input type="hidden" name="anio" value="{{ $anio }}">
            <input type="hidden" name="mes"  value="{{ $mes }}">
            <button type="submit" class="btn waves-effect waves-light teal darken-1">
                <i class="material-icons left">sync</i>
                Sincronizar desde SII
            </button>
        </form>
        <small class="grey-text" style="margin-left:8px">
            Consulta el período {{ $periodo }} en el portal SII BTE
        </small>
    </div>
    @else
    <div class="card-panel orange lighten-4" style="margin:12px 0">
        <i class="material-icons tiny">warning</i>
        Credenciales SII no configuradas. Agrega <code>SII_RUT</code> y <code>SII_CLAVE</code> al <code>.env</code>.
    </div>
    @endif

    {{-- Tarjetas resumen --}}
    <div class="row" style="margin:20px 0 8px">
        <div class="col s6 m3">
            <div class="card-panel center" style="padding:12px; border-top:3px solid #039B7B">
                <p class="grey-text" style="margin:0; font-size:12px">BOLETAS</p>
                <p style="margin:4px 0 0; font-size:1.5rem; font-weight:700">{{ $resumen['total_bte'] }}</p>
            </div>
        </div>
        <div class="col s6 m3">
            <div class="card-panel center" style="padding:12px; border-top:3px solid #039B7B">
                <p class="grey-text" style="margin:0; font-size:12px">BRUTO</p>
                <p style="margin:4px 0 0; font-size:1.2rem; font-weight:700">
                    ${{ number_format($resumen['monto_bruto'], 0, ',', '.') }}
                </p>
            </div>
        </div>
        <div class="col s6 m3">
            <div class="card-panel center" style="padding:12px; border-top:3px solid #e53935">
                <p class="grey-text" style="margin:0; font-size:12px">RETENCIÓN</p>
                <p style="margin:4px 0 0; font-size:1.2rem; font-weight:700; color:#e53935">
                    ${{ number_format($resumen['monto_retenido'], 0, ',', '.') }}
                </p>
            </div>
        </div>
        <div class="col s6 m3">
            <div class="card-panel center" style="padding:12px; border-top:3px solid #1565c0">
                <p class="grey-text" style="margin:0; font-size:12px">LÍQUIDO PAGADO</p>
                <p style="margin:4px 0 0; font-size:1.2rem; font-weight:700; color:#1565c0">
                    ${{ number_format($resumen['monto_pagado'], 0, ',', '.') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Tabla detalle --}}
    <div class="card-panel" style="padding:16px; overflow-x:auto">
        @if($honorarios->isEmpty())
        <p class="center grey-text" style="padding:32px 0; margin:0">
            Sin boletas BTE para {{ $nombreMes }}.
            @if($credencialesOk) Usa el botón <strong>Sincronizar</strong> para obtenerlas. @endif
        </p>
        @else
        <table class="striped" style="font-size:13px; width:100%">
            <thead>
                <tr style="background:#eceff1">
                    <th>Folio</th>
                    <th>Emisor</th>
                    <th>RUT</th>
                    <th class="center">Estado</th>
                    <th class="right-align">Fecha</th>
                    <th class="right-align">Bruto</th>
                    <th class="right-align">Retención</th>
                    <th class="right-align">Líquido</th>
                </tr>
            </thead>
            <tbody>
                @foreach($honorarios as $h)
                <tr>
                    <td>{{ $h->folio }}</td>
                    <td>{{ $h->nombre_emisor }}</td>
                    <td class="grey-text" style="font-size:12px">{{ $h->rut_emisor }}</td>
                    <td class="center">
                        @if($h->estado === 'Anulada')
                            <span style="color:#e53935; font-size:11px; font-weight:600">ANULADA</span>
                        @elseif($h->estado === 'Pagada')
                            <span style="color:#2e7d32; font-size:11px; font-weight:600">PAGADA</span>
                        @else
                            <span style="color:#f57f17; font-size:11px; font-weight:600">{{ strtoupper($h->estado) }}</span>
                        @endif
                    </td>
                    <td class="right-align grey-text">
                        {{ $h->fecha_emision ? \Carbon\Carbon::parse($h->fecha_emision)->format('d/m/Y') : '—' }}
                    </td>
                    <td class="right-align">
                        ${{ number_format($h->monto_bruto, 0, ',', '.') }}
                    </td>
                    <td class="right-align" style="color:#e53935">
                        ${{ number_format($h->monto_retenido, 0, ',', '.') }}
                    </td>
                    <td class="right-align" style="color:#1565c0; font-weight:600">
                        ${{ number_format($h->monto_pagado, 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f0faf7; font-weight:700; font-size:13px">
                    <td colspan="5">TOTAL {{ $nombreMes }}</td>
                    <td class="right-align">${{ number_format($resumen['monto_bruto'], 0, ',', '.') }}</td>
                    <td class="right-align" style="color:#e53935">${{ number_format($resumen['monto_retenido'], 0, ',', '.') }}</td>
                    <td class="right-align" style="color:#1565c0">${{ number_format($resumen['monto_pagado'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
        @endif
    </div>

    {{-- Resumen anual --}}
    @if($resumenAnual->isNotEmpty())
    <div class="card-panel" style="padding:16px; overflow-x:auto; margin-top:16px">
        <p style="font-weight:600; margin:0 0 12px; font-size:14px">
            <i class="material-icons tiny">bar_chart</i> Resumen {{ $anio }}
        </p>
        <table class="striped" style="font-size:13px; width:100%">
            <thead>
                <tr style="background:#eceff1">
                    <th>Período</th>
                    <th class="center">Boletas</th>
                    <th class="right-align">Bruto</th>
                    <th class="right-align">Retención</th>
                    <th class="right-align">Líquido</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resumenAnual as $r)
                <tr>
                    <td>
                        <a href="{{ route('backoffice.honorarios.index', [
                            'anio' => substr($r->periodo, 0, 4),
                            'mes'  => (int) substr($r->periodo, 4, 2)
                        ]) }}" style="color:#039B7B">
                            {{ \Carbon\Carbon::createFromFormat('Ym', $r->periodo)->locale('es')->isoFormat('MMMM YYYY') }}
                        </a>
                    </td>
                    <td class="center">{{ $r->cantidad }}</td>
                    <td class="right-align">${{ number_format($r->total_bruto, 0, ',', '.') }}</td>
                    <td class="right-align" style="color:#e53935">${{ number_format($r->total_retenido, 0, ',', '.') }}</td>
                    <td class="right-align" style="color:#1565c0; font-weight:600">${{ number_format($r->total_pagado, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>
@endsection
