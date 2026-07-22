@extends('themes.backoffice.layouts.admin')

@section('title', 'SII – Gastos Semanales')

@section('breadcrumbs')
<li><a href="{{ route('backoffice.sii.index') }}">SII</a></li>
<li>Gastos Semanales</li>
@endsection

@section('content')
<div class="section">

    {{-- Header + selector de periodo --}}
    <div class="card-panel">
        <div class="row" style="margin-bottom:0; align-items:center; display:flex; justify-content:space-between; flex-wrap:wrap;">
            <div>
                <h5 style="margin:0;">Gastos SII — {{ $nombreMes }}</h5>
                <span class="grey-text" style="font-size:13px;">
                    Semana: {{ $inicioSemana->format('d/m/Y') }} al {{ $finSemana->format('d/m/Y') }}
                    &nbsp;·&nbsp; Importación automática todos los domingos a las 21:10
                </span>
            </div>
            <div style="display:flex; gap:8px; align-items:center; margin-top:8px;">
                <form method="GET" style="display:inline-flex; gap:6px; align-items:center;">
                    <select name="mes" onchange="this.form.submit()" style="width:120px;">
                        @foreach(range(1,12) as $m)
                            <option value="{{ $m }}" {{ $m == $mes ? 'selected' : '' }}>
                                {{ ucfirst(\Carbon\Carbon::create($anio, $m, 1)->locale('es')->isoFormat('MMMM')) }}
                            </option>
                        @endforeach
                    </select>
                    <select name="anio" onchange="this.form.submit()" style="width:80px;">
                        @foreach(range(now()->year, 2024, -1) as $a)
                            <option value="{{ $a }}" {{ $a == $anio ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('backoffice.sii.index') }}" class="btn-flat waves-effect grey-text">
                    <i class="material-icons left">arrow_back</i>Volver
                </a>
            </div>
        </div>
    </div>

    {{-- Tarjetas de totales --}}
    <div class="row">
        <div class="col s12 m6">
            <div class="card-panel blue lighten-5 center-align">
                <p class="blue-text text-darken-2" style="margin:0; font-size:12px; font-weight:600; letter-spacing:.5px;">
                    GASTO ESTA SEMANA
                </p>
                <h4 class="blue-text text-darken-3" style="margin:6px 0;">
                    $ {{ number_format($totalSemana, 0, ',', '.') }}
                </h4>
                <span class="grey-text" style="font-size:12px;">
                    {{ $inicioSemana->format('d/m') }} — {{ $finSemana->format('d/m/Y') }}
                </span>
            </div>
        </div>
        <div class="col s12 m6">
            <div class="card-panel teal lighten-5 center-align">
                <p class="teal-text text-darken-2" style="margin:0; font-size:12px; font-weight:600; letter-spacing:.5px;">
                    ACUMULADO {{ strtoupper($nombreMes) }}
                </p>
                <h4 class="teal-text text-darken-3" style="margin:6px 0;">
                    $ {{ number_format($totalMes, 0, ',', '.') }}
                </h4>
                <span class="grey-text" style="font-size:12px;">
                    Facturas importadas desde SII
                </span>
            </div>
        </div>
    </div>

    {{-- Tabla 2 columnas --}}
    <div class="card-panel">
        <h6 style="margin-top:0; color:#555;">Detalle por proveedor</h6>

        @if(empty($filas))
            <div class="center grey-text" style="padding:30px;">
                <i class="material-icons large">inbox</i>
                <p>No hay gastos SII importados para este periodo.<br>
                   El sistema importa automáticamente cada domingo a las 21:10.</p>
                <a href="{{ route('backoffice.sii.listar', ['anio' => $anio, 'mes' => $mes]) }}"
                   class="btn waves-effect teal">
                    <i class="material-icons left">cloud_download</i>Importar ahora
                </a>
            </div>
        @else
        <table class="striped responsive-table">
            <thead>
                <tr style="background:#f5f5f5;">
                    <th style="width:40%;">Proveedor</th>
                    <th style="width:15%;" class="grey-text" style="font-size:11px;">Categoría</th>
                    <th class="right blue-text text-darken-2">Esta semana</th>
                    <th class="right teal-text text-darken-2">Acumulado mes</th>
                </tr>
            </thead>
            <tbody>
                @php $catActual = ''; @endphp
                @foreach($filas as $fila)
                    @if($fila->categoria !== $catActual)
                        @php $catActual = $fila->categoria; @endphp
                        <tr style="background:#eeeeee;">
                            <td colspan="4" style="font-weight:600; font-size:12px; color:#555; padding:4px 12px;">
                                {{ strtoupper($catActual) }}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td style="font-size:13px;">{{ $fila->subcategoria }}</td>
                        <td class="grey-text" style="font-size:11px;">{{ $fila->categoria }}</td>
                        <td class="right">
                            @if($fila->total_semana > 0)
                                <span class="blue-text text-darken-2" style="font-weight:500;">
                                    $ {{ number_format($fila->total_semana, 0, ',', '.') }}
                                </span>
                            @else
                                <span class="grey-text">—</span>
                            @endif
                        </td>
                        <td class="right">
                            <strong>$ {{ number_format($fila->total_mes, 0, ',', '.') }}</strong>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#e8f5e9; font-weight:700;">
                    <td colspan="2"><strong>TOTAL</strong></td>
                    <td class="right blue-text text-darken-2">
                        $ {{ number_format($totalSemana, 0, ',', '.') }}
                    </td>
                    <td class="right teal-text text-darken-2">
                        $ {{ number_format($totalMes, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
        @endif
    </div>

</div>
@endsection

@section('foot')
<script>
$(document).ready(function () {
    $('select').material_select();
});
</script>
@endsection
