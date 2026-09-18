@extends('themes.backoffice.layouts.admin')

@section('title', 'Resumen Honorarios BTE — ' . $anio)

@section('breadcrumbs')
<li><a href="{{ route('backoffice.honorarios.index') }}">Honorarios BTE</a></li>
<li>Resumen Anual</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.honorarios.index') }}" class="grey-text text-darken-2">Ver por mes</a></li>
<li><a href="{{ route('backoffice.sii.resumen') }}" class="grey-text text-darken-2">RCV Compras</a></li>
@endsection

@section('content')
<div class="section">

    {{-- Header --}}
    <div class="row valign-wrapper" style="margin-bottom:0">
        <div class="col s12 m8">
            <h5 class="grey-text text-darken-2" style="margin:0 0 4px">
                <i class="material-icons left" style="font-size:1.5rem">receipt</i>
                Honorarios BTE — Resumen {{ $anio }}
            </h5>
            <p class="grey-text" style="margin:0; font-size:.85rem">
                Gasto anual por emisor y evolución mensual.
            </p>
        </div>
        <div class="col s12 m4" style="text-align:right">
            <form method="GET" action="{{ route('backoffice.honorarios.resumen') }}"
                  style="display:inline-flex; align-items:center; gap:8px">
                <select name="anio" class="browser-default" onchange="this.form.submit()"
                        style="width:90px; border:1px solid #ccc; border-radius:4px; padding:4px 8px">
                    @foreach(range(date('Y'), 2020) as $a)
                    <option value="{{ $a }}" {{ $anio == $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    {{-- Tarjetas totales --}}
    <div class="row" style="margin:20px 0 8px">
        <div class="col s6 m3">
            <div class="card-panel center" style="padding:12px; border-top:3px solid #039B7B">
                <p class="grey-text" style="margin:0; font-size:12px">TOTAL BOLETAS</p>
                <p style="margin:4px 0 0; font-size:1.5rem; font-weight:700">{{ $totalesAnio['cantidad'] }}</p>
            </div>
        </div>
        <div class="col s6 m3">
            <div class="card-panel center" style="padding:12px; border-top:3px solid #039B7B">
                <p class="grey-text" style="margin:0; font-size:12px">BRUTO {{ $anio }}</p>
                <p style="margin:4px 0 0; font-size:1.2rem; font-weight:700">
                    ${{ number_format($totalesAnio['bruto'], 0, ',', '.') }}
                </p>
            </div>
        </div>
        <div class="col s6 m3">
            <div class="card-panel center" style="padding:12px; border-top:3px solid #e53935">
                <p class="grey-text" style="margin:0; font-size:12px">RETENCIÓN {{ $anio }}</p>
                <p style="margin:4px 0 0; font-size:1.2rem; font-weight:700; color:#e53935">
                    ${{ number_format($totalesAnio['retenido'], 0, ',', '.') }}
                </p>
            </div>
        </div>
        <div class="col s6 m3">
            <div class="card-panel center" style="padding:12px; border-top:3px solid #1565c0">
                <p class="grey-text" style="margin:0; font-size:12px">LÍQUIDO {{ $anio }}</p>
                <p style="margin:4px 0 0; font-size:1.2rem; font-weight:700; color:#1565c0">
                    ${{ number_format($totalesAnio['pagado'], 0, ',', '.') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Por emisor --}}
    <div class="card-panel" style="padding:16px; overflow-x:auto">
        <p style="font-weight:600; margin:0 0 12px; font-size:14px">
            <i class="material-icons tiny">person</i> Gasto por emisor — {{ $anio }}
        </p>
        @if($porEmisor->isEmpty())
        <p class="center grey-text" style="padding:24px 0; margin:0">Sin datos para {{ $anio }}.</p>
        @else
        <table class="striped" style="font-size:13px; width:100%">
            <thead>
                <tr style="background:#eceff1">
                    <th>Emisor</th>
                    <th>RUT</th>
                    <th class="center">Boletas</th>
                    <th class="right-align">Bruto</th>
                    <th class="right-align">Retención</th>
                    <th class="right-align">Líquido</th>
                </tr>
            </thead>
            <tbody>
                @foreach($porEmisor as $e)
                <tr>
                    <td>{{ $e->nombre_emisor }}</td>
                    <td class="grey-text" style="font-size:12px">{{ $e->rut_emisor }}</td>
                    <td class="center">{{ $e->cantidad_bte }}</td>
                    <td class="right-align">${{ number_format($e->total_bruto, 0, ',', '.') }}</td>
                    <td class="right-align" style="color:#e53935">${{ number_format($e->total_retenido, 0, ',', '.') }}</td>
                    <td class="right-align" style="color:#1565c0; font-weight:600">${{ number_format($e->total_pagado, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f0faf7; font-weight:700">
                    <td colspan="3">TOTAL</td>
                    <td class="right-align">${{ number_format($totalesAnio['bruto'], 0, ',', '.') }}</td>
                    <td class="right-align" style="color:#e53935">${{ number_format($totalesAnio['retenido'], 0, ',', '.') }}</td>
                    <td class="right-align" style="color:#1565c0">${{ number_format($totalesAnio['pagado'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
        @endif
    </div>

    {{-- Evolución mensual --}}
    @if($evolucionMensual->isNotEmpty())
    <div class="card-panel" style="padding:16px; overflow-x:auto; margin-top:4px">
        <p style="font-weight:600; margin:0 0 12px; font-size:14px">
            <i class="material-icons tiny">show_chart</i> Evolución mensual — {{ $anio }}
        </p>
        <table class="striped" style="font-size:13px; width:100%">
            <thead>
                <tr style="background:#eceff1">
                    <th>Período</th>
                    <th class="center">Boletas</th>
                    <th class="right-align">Bruto</th>
                    <th class="right-align">Retención</th>
                </tr>
            </thead>
            <tbody>
                @foreach($evolucionMensual as $em)
                <tr>
                    <td>
                        <a href="{{ route('backoffice.honorarios.index', [
                            'anio' => substr($em->periodo, 0, 4),
                            'mes'  => (int) substr($em->periodo, 4, 2)
                        ]) }}" style="color:#039B7B">
                            {{ \Carbon\Carbon::createFromFormat('Ym', $em->periodo)->locale('es')->isoFormat('MMMM YYYY') }}
                        </a>
                    </td>
                    <td class="center">{{ $em->cantidad }}</td>
                    <td class="right-align">${{ number_format($em->total_bruto, 0, ',', '.') }}</td>
                    <td class="right-align" style="color:#e53935">${{ number_format($em->total_retenido, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>
@endsection
