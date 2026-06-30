@extends('themes.backoffice.layouts.admin')

@section('title', 'Honorarios – Resumen por Emisor ' . $anio)

@section('breadcrumbs')
<li><a href="{{ route('backoffice.honorarios.index') }}">Honorarios BTE</a></li>
<li>Resumen {{ $anio }}</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.honorarios.index') }}" class="grey-text text-darken-2">Listado mensual</a></li>
@endsection

@section('content')
<div class="section">

    {{-- Selector de año --}}
    <div class="row" style="margin-bottom:0">
        <div class="col s12 m6">
            <h5 class="grey-text text-darken-2" style="margin:0">
                <i class="material-icons left" style="font-size:1.5rem">people</i>
                Gasto por Emisor — {{ $anio }}
            </h5>
        </div>
        <div class="col s12 m6" style="text-align:right;padding-top:4px">
            <form method="GET" action="{{ route('backoffice.honorarios.resumen') }}" style="display:inline-flex;gap:8px;align-items:center">
                <select name="anio" class="browser-default" style="width:90px;border:1px solid #ccc;border-radius:4px;padding:4px 8px">
                    @foreach(range(date('Y'), 2020) as $a)
                    <option value="{{ $a }}" {{ $anio == $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-flat waves-effect"><i class="material-icons">search</i></button>
            </form>
        </div>
    </div>

    {{-- Totales --}}
    <div class="row" style="margin-top:16px;margin-bottom:8px">
        <div class="col s6 m3">
            <div class="card-panel center-align" style="padding:12px 8px;margin:4px 0">
                <span class="grey-text" style="font-size:.75rem;display:block">Total BTE</span>
                <span style="font-size:1.8rem;font-weight:700;color:#455a64">{{ $totalesAnio['cantidad'] }}</span>
            </div>
        </div>
        <div class="col s6 m3">
            <div class="card-panel center-align" style="padding:12px 8px;margin:4px 0">
                <span class="grey-text" style="font-size:.75rem;display:block">Bruto Total</span>
                <span style="font-size:1.2rem;font-weight:700;color:#d32f2f">${{ number_format($totalesAnio['bruto'],0,',','.') }}</span>
            </div>
        </div>
        <div class="col s6 m3">
            <div class="card-panel center-align" style="padding:12px 8px;margin:4px 0">
                <span class="grey-text" style="font-size:.75rem;display:block">Retención Total</span>
                <span style="font-size:1.2rem;font-weight:700;color:#f57c00">${{ number_format($totalesAnio['retenido'],0,',','.') }}</span>
            </div>
        </div>
        <div class="col s6 m3">
            <div class="card-panel center-align" style="padding:12px 8px;margin:4px 0">
                <span class="grey-text" style="font-size:.75rem;display:block">Pagado Total</span>
                <span style="font-size:1.2rem;font-weight:700;color:#388e3c">${{ number_format($totalesAnio['pagado'],0,',','.') }}</span>
            </div>
        </div>
    </div>

    {{-- Tabla por emisor --}}
    <div class="card" style="border-radius:8px;overflow:hidden">
        <div class="card-content" style="padding:0">
            @if($porEmisor->isEmpty())
            <div style="padding:40px;text-align:center;color:#9e9e9e">
                <i class="material-icons" style="font-size:3rem;display:block;margin-bottom:8px">people_outline</i>
                No hay BTE registradas para {{ $anio }}.
            </div>
            @else
            <table class="striped responsive-table" style="margin:0">
                <thead>
                    <tr style="background:#eceff1">
                        <th>RUT</th>
                        <th>Nombre Emisor</th>
                        <th class="center-align">BTE</th>
                        <th class="right-align">Bruto</th>
                        <th class="right-align">Retención</th>
                        <th class="right-align">Pagado</th>
                        <th class="center-align">% del total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($porEmisor as $e)
                    @php $pct = $totalesAnio['bruto'] > 0 ? round($e->total_bruto / $totalesAnio['bruto'] * 100, 1) : 0; @endphp
                    <tr>
                        <td style="font-family:monospace;white-space:nowrap">{{ $e->rut_emisor }}</td>
                        <td>{{ $e->nombre_emisor ?: '—' }}</td>
                        <td class="center-align">{{ $e->cantidad_bte }}</td>
                        <td class="right-align red-text">${{ number_format($e->total_bruto, 0, ',', '.') }}</td>
                        <td class="right-align orange-text text-darken-2">${{ number_format($e->total_retenido, 0, ',', '.') }}</td>
                        <td class="right-align green-text text-darken-2">${{ number_format($e->total_pagado, 0, ',', '.') }}</td>
                        <td class="center-align">
                            <div style="background:#e0e0e0;border-radius:4px;height:8px;width:100%;min-width:60px">
                                <div style="background:#1976d2;height:8px;border-radius:4px;width:{{ $pct }}%"></div>
                            </div>
                            <small class="grey-text">{{ $pct }}%</small>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#f5f5f5;font-weight:700">
                        <td colspan="2">TOTAL</td>
                        <td class="center-align">{{ $totalesAnio['cantidad'] }}</td>
                        <td class="right-align red-text">${{ number_format($totalesAnio['bruto'],0,',','.') }}</td>
                        <td class="right-align orange-text text-darken-2">${{ number_format($totalesAnio['retenido'],0,',','.') }}</td>
                        <td class="right-align green-text text-darken-2">${{ number_format($totalesAnio['pagado'],0,',','.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            @endif
        </div>
    </div>

    {{-- Evolución mensual --}}
    @if($evolucionMensual->isNotEmpty())
    <div style="margin-top:24px">
        <h6 class="grey-text text-darken-2">Evolución mensual {{ $anio }}</h6>
        @php $maxBruto = $evolucionMensual->max('total_bruto') ?: 1; @endphp
        <div class="card" style="border-radius:8px;overflow:hidden">
            <table class="striped" style="font-size:.9rem;margin:0">
                <thead>
                    <tr style="background:#eceff1">
                        <th>Mes</th>
                        <th class="right-align">Bruto</th>
                        <th class="right-align">Retención</th>
                        <th class="center-align">BTE</th>
                        <th>Distribución</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($evolucionMensual as $m)
                    @php
                        $mesNum = (int) substr($m->periodo, 4, 2);
                        $meses  = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                        $pctBar = round($m->total_bruto / $maxBruto * 100, 0);
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('backoffice.honorarios.index', ['anio' => substr($m->periodo,0,4), 'mes' => $mesNum]) }}">
                                {{ $meses[$mesNum] ?? $m->periodo }}
                            </a>
                        </td>
                        <td class="right-align">${{ number_format($m->total_bruto, 0, ',', '.') }}</td>
                        <td class="right-align orange-text">${{ number_format($m->total_retenido, 0, ',', '.') }}</td>
                        <td class="center-align">{{ $m->cantidad }}</td>
                        <td>
                            <div style="background:#e0e0e0;border-radius:4px;height:10px">
                                <div style="background:#d32f2f;height:10px;border-radius:4px;width:{{ $pctBar }}%"></div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
