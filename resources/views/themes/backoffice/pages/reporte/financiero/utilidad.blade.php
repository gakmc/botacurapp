@extends('themes.backoffice.layouts.admin')

@section('title', 'Utilidad – ' . $nombreMes)

@section('breadcrumbs')
<li>Finanzas</li>
<li>Utilidad</li>
@endsection

@section('content')
<div class="section">

    {{-- Header --}}
    <div class="row valign-wrapper" style="margin-bottom:8px">
        <div class="col s12 m7">
            <h5 class="grey-text text-darken-2" style="margin:0 0 4px">
                <i class="material-icons left" style="font-size:1.5rem">show_chart</i>
                Utilidad — {{ ucfirst($nombreMes) }}
            </h5>
            <p class="grey-text" style="margin:0;font-size:.85rem">
                Ingresos de la app vs Egresos SII. Utilidad = Ingresos − (Facturas + Honorarios + PPM).
            </p>
        </div>
        <div class="col s12 m5" style="text-align:right">
            <form method="GET" action="{{ route('backoffice.finanzas.utilidad') }}" style="display:inline-flex;gap:8px;align-items:center">
                <select name="mes" class="browser-default" style="width:130px;border:1px solid #ccc;border-radius:4px;padding:4px 8px">
                    @foreach($mesesNombres as $n => $nombre)
                    <option value="{{ $n }}" {{ $mes==$n ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
                <select name="anio" class="browser-default" style="width:80px;border:1px solid #ccc;border-radius:4px;padding:4px 8px">
                    @foreach(range(date('Y'), 2020) as $a)
                    <option value="{{ $a }}" {{ $anio==$a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-flat waves-effect"><i class="material-icons">search</i></button>
            </form>
        </div>
    </div>

    {{-- ═══ KPI CARDS ═══ --}}
    <div class="row" style="margin-bottom:0">
        {{-- Ingresos --}}
        <div class="col s6 m3">
            <div class="card-panel" style="padding:14px 12px;margin:4px 0;border-left:4px solid #2E7D32;background:#f1f8e9">
                <span class="grey-text" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;display:block">Total Ingresos</span>
                <span style="font-size:1.4rem;font-weight:700;color:#2E7D32">${{ number_format($totalIngresos,0,',','.') }}</span>
                <span class="grey-text" style="font-size:.75rem;display:block">Ventas app</span>
            </div>
        </div>
        {{-- Egresos --}}
        <div class="col s6 m3">
            <div class="card-panel" style="padding:14px 12px;margin:4px 0;border-left:4px solid #B71C1C;background:#fff8f8">
                <span class="grey-text" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;display:block">Total Egresos</span>
                <span style="font-size:1.4rem;font-weight:700;color:#B71C1C">${{ number_format($totalEgresos,0,',','.') }}</span>
                <span class="grey-text" style="font-size:.75rem;display:block">{{ $totalIngresos>0 ? round(($totalEgresos/$totalIngresos)*100,1) : 0 }}% de ingresos</span>
            </div>
        </div>
        {{-- Utilidad --}}
        <div class="col s6 m3">
            @php $positivo = $utilidad >= 0; @endphp
            <div class="card-panel" style="padding:14px 12px;margin:4px 0;border-left:4px solid {{ $positivo ? '#1565C0' : '#E53935' }};background:{{ $positivo ? '#e8f0fe' : '#fff8f8' }}">
                <span class="grey-text" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;display:block">Utilidad Neta</span>
                <span style="font-size:1.4rem;font-weight:700;color:{{ $positivo ? '#1565C0' : '#E53935' }}">
                    {{ $positivo ? '' : '-' }}${{ number_format(abs($utilidad),0,',','.') }}
                </span>
                <span style="font-size:.75rem;display:block;color:{{ $positivo ? '#388E3C' : '#E53935' }}">
                    {{ $positivo ? '▲' : '▼' }} {{ abs($margen) }}% margen
                </span>
            </div>
        </div>
        {{-- Margen % --}}
        <div class="col s6 m3">
            <div class="card-panel" style="padding:14px 12px;margin:4px 0;border-left:4px solid #6A1B9A;background:#f5f0ff">
                <span class="grey-text" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;display:block">Margen</span>
                <span style="font-size:1.4rem;font-weight:700;color:#6A1B9A">{{ $margen }}%</span>
                <span class="grey-text" style="font-size:.75rem;display:block">Utilidad / Ingresos</span>
            </div>
        </div>
    </div>

    {{-- ═══ DESGLOSE PRINCIPAL ═══ --}}
    <div class="row" style="margin-top:8px">

        {{-- INGRESOS --}}
        <div class="col s12 m6">
            <div class="card" style="border-radius:8px">
                <div class="card-content" style="padding:16px 20px">
                    <p style="font-weight:700;margin:0 0 12px;color:#2E7D32;font-size:.95rem">
                        <i class="material-icons tiny">arrow_downward</i> Ingresos
                    </p>
                    <table style="width:100%;font-size:.88rem">
                        <tbody>
                            @php
                                $ingRows = [
                                    ['Programas / Abonos',  $abonos],
                                    ['Consumos (bar/spa)',   $consumos],
                                    ['Servicios Extra',      $servicios],
                                    ['Venta Directa',        $directas],
                                    ['Poro Poro',            $poro],
                                ];
                            @endphp
                            @foreach($ingRows as $row)
                            @if($row[1] > 0)
                            <tr style="border-bottom:1px solid #f5f5f5">
                                <td style="padding:5px 0;color:#555">{{ $row[0] }}</td>
                                <td style="text-align:right;padding:5px 0">${{ number_format($row[1],0,',','.') }}</td>
                                <td style="text-align:right;padding:5px 0;color:#9e9e9e;font-size:.8rem;width:40px">
                                    {{ $totalIngresos > 0 ? round(($row[1]/$totalIngresos)*100,1) : 0 }}%
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:#f1f8e9">
                                <td style="padding:8px 0;font-weight:700">Total Ingresos</td>
                                <td style="text-align:right;font-weight:700;color:#2E7D32">${{ number_format($totalIngresos,0,',','.') }}</td>
                                <td style="text-align:right;font-weight:700;color:#2E7D32">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- EGRESOS --}}
        <div class="col s12 m6">
            <div class="card" style="border-radius:8px">
                <div class="card-content" style="padding:16px 20px">
                    <p style="font-weight:700;margin:0 0 12px;color:#B71C1C;font-size:.95rem">
                        <i class="material-icons tiny">arrow_upward</i> Egresos
                    </p>

                    {{-- Facturas SII --}}
                    <div style="margin-bottom:14px">
                        <div style="display:flex;justify-content:space-between;font-size:.88rem;margin-bottom:3px">
                            <a href="{{ route('backoffice.sii.index') }}" style="color:#555;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Facturas SII (compras)</a>
                            <span>
                                <strong>${{ number_format($facturasSii,0,',','.') }}</strong>
                                <span class="grey-text" style="font-size:.8rem;margin-left:6px">{{ $breakdown[0]['pct'] }}%</span>
                            </span>
                        </div>
                        <div style="background:#e0e0e0;border-radius:4px;height:6px">
                            <div style="background:#EF5350;border-radius:4px;height:6px;width:{{ min($breakdown[0]['pct'],100) }}%"></div>
                        </div>
                    </div>

                    {{-- Retención Honorarios --}}
                    <div style="margin-bottom:14px">
                        <div style="display:flex;justify-content:space-between;font-size:.88rem;margin-bottom:3px">
                            <a href="{{ route('backoffice.honorarios.index') }}" style="color:#555;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Retención honorarios (SII)</a>
                            <span>
                                <strong>${{ number_format($honorariosRetencion,0,',','.') }}</strong>
                                <span class="grey-text" style="font-size:.8rem;margin-left:6px">{{ $breakdown[1]['pct'] }}%</span>
                            </span>
                        </div>
                        <div style="background:#e0e0e0;border-radius:4px;height:6px">
                            <div style="background:#FF7043;border-radius:4px;height:6px;width:{{ min($breakdown[1]['pct'],100) }}%"></div>
                        </div>
                        <div style="font-size:.75rem;color:#9e9e9e;margin-top:2px">
                            15.25% del bruto BTE · Neto a trabajadores: ${{ number_format($honorariosNeto,0,',','.') }} (ya en sueldos)
                        </div>
                    </div>

                    {{-- Sueldos --}}
                    <div style="margin-bottom:14px">
                        <div style="display:flex;justify-content:space-between;font-size:.88rem;margin-bottom:3px">
                            <a href="{{ route('backoffice.sueldos.index') }}" style="color:#555;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Sueldos pagados</a>
                            <span>
                                <strong>${{ number_format($sueldosPagados,0,',','.') }}</strong>
                                <span class="grey-text" style="font-size:.8rem;margin-left:6px">{{ $breakdown[2]['pct'] }}%</span>
                            </span>
                        </div>
                        <div style="background:#e0e0e0;border-radius:4px;height:6px">
                            <div style="background:#26A69A;border-radius:4px;height:6px;width:{{ min($breakdown[2]['pct'],100) }}%"></div>
                        </div>
                        <div style="font-size:.75rem;color:#9e9e9e;margin-top:2px">
                            Sueldo base (valor_dia) · excluye propinas de funcionarios
                        </div>
                    </div>

                    {{-- F29 PPM --}}
                    <div style="margin-bottom:14px">
                        <div style="display:flex;justify-content:space-between;font-size:.88rem;margin-bottom:3px">
                            <a href="{{ route('backoffice.impuesto.index') }}" style="color:#555;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">F29 PPM (0.25% × ventas SII)</a>
                            <span>
                                <strong>${{ number_format($ppm,0,',','.') }}</strong>
                                <span class="grey-text" style="font-size:.8rem;margin-left:6px">{{ $breakdown[3]['pct'] }}%</span>
                            </span>
                        </div>
                        <div style="background:#e0e0e0;border-radius:4px;height:6px">
                            <div style="background:#AB47BC;border-radius:4px;height:6px;width:{{ min($breakdown[3]['pct'],100) }}%"></div>
                        </div>
                        @if($ventasSii == 0)
                        <div style="font-size:.75rem;color:#FF8F00;margin-top:2px">
                            <i class="material-icons" style="font-size:.8rem;vertical-align:middle">warning</i>
                            Sincroniza ventas SII en <a href="{{ route('backoffice.impuesto.index', ['anio'=>$anio,'mes'=>$mes]) }}">F29 Estimado</a> para calcular el PPM.
                        </div>
                        @endif
                    </div>

                    <div style="border-top:2px solid #ffcdd2;padding-top:10px">
                        <div style="display:flex;justify-content:space-between;font-size:.95rem;font-weight:700">
                            <span style="color:#B71C1C">Total Egresos</span>
                            <span style="color:#B71C1C">
                                ${{ number_format($totalEgresos,0,',','.') }}
                                <span style="font-size:.8rem;margin-left:4px">({{ $totalIngresos>0 ? round(($totalEgresos/$totalIngresos)*100,1) : 0 }}%)</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- UTILIDAD card --}}
            <div class="card-panel" style="margin:0;padding:12px 20px;background:{{ $positivo ? '#e8f0fe' : '#ffebee' }};border-radius:8px">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-weight:700;font-size:1rem;color:{{ $positivo ? '#1565C0' : '#B71C1C' }}">
                        Utilidad Neta {{ ucfirst($nombreMes) }}
                    </span>
                    <span style="font-weight:700;font-size:1.3rem;color:{{ $positivo ? '#1565C0' : '#B71C1C' }}">
                        {{ $positivo ? '' : '-' }}${{ number_format(abs($utilidad),0,',','.') }}
                        <small style="font-size:.8rem"> ({{ $margen }}%)</small>
                    </span>
                </div>
            </div>
        </div>

    </div>

    {{-- ═══ GRÁFICO ANUAL ═══ --}}
    <div class="card" style="border-radius:8px;margin-top:12px">
        <div class="card-content" style="padding:16px 20px">
            <p class="grey-text text-darken-2" style="font-weight:600;margin:0 0 16px;font-size:.95rem">
                <i class="material-icons tiny">bar_chart</i> Ingresos vs Egresos — {{ $anio }}
            </p>
            <canvas id="grafico-utilidad" height="80"></canvas>
        </div>
    </div>

    {{-- ═══ TABLA ANUAL ═══ --}}
    <div style="margin-top:16px">
        <h6 class="grey-text text-darken-2" style="margin-bottom:8px">Resumen Anual {{ $anio }}</h6>
        <div style="overflow-x:auto">
        <table class="striped" style="font-size:.85rem;min-width:560px">
            <thead>
                <tr style="background:#eceff1">
                    <th>Mes</th>
                    <th class="right-align">Ingresos</th>
                    <th class="right-align">Egresos</th>
                    <th class="right-align">Utilidad</th>
                    <th class="right-align">Margen</th>
                </tr>
            </thead>
            <tbody>
                @php $totIng=0; $totEgr=0; @endphp
                @foreach($resumenAnual as $m => $d)
                @php $esMes = ($m == $mes); @endphp
                <tr class="{{ $esMes ? 'blue lighten-5' : '' }}">
                    <td>
                        <a href="{{ route('backoffice.finanzas.utilidad', ['anio'=>$anio,'mes'=>$m]) }}"
                           class="{{ $esMes ? 'blue-text font-weight-bold' : '' }}">
                            {{ $mesesNombres[$m] }}
                        </a>
                    </td>
                    @if($d === null)
                        <td class="right-align grey-text" colspan="4">—</td>
                    @else
                    @php $totIng += $d['ing']; $totEgr += $d['egr']; @endphp
                    <td class="right-align green-text text-darken-2">${{ number_format($d['ing'],0,',','.') }}</td>
                    <td class="right-align red-text">${{ number_format($d['egr'],0,',','.') }}</td>
                    <td class="right-align" style="color:{{ $d['utilidad']>=0 ? '#1565C0' : '#E53935' }};font-weight:600">
                        {{ $d['utilidad']>=0 ? '' : '-' }}${{ number_format(abs($d['utilidad']),0,',','.') }}
                    </td>
                    <td class="right-align" style="color:{{ $d['margen']>=0 ? '#388E3C' : '#E53935' }}">
                        {{ $d['margen'] }}%
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php
                    $totUtil = $totIng - $totEgr;
                    $totMarg = $totIng > 0 ? round(($totUtil/$totIng)*100,1) : 0;
                @endphp
                <tr style="background:#f0faf7;font-weight:700">
                    <td>TOTAL {{ $anio }}</td>
                    <td class="right-align green-text text-darken-2">${{ number_format($totIng,0,',','.') }}</td>
                    <td class="right-align red-text">${{ number_format($totEgr,0,',','.') }}</td>
                    <td class="right-align" style="color:{{ $totUtil>=0 ? '#1565C0' : '#E53935' }}">
                        {{ $totUtil>=0?'':'-' }}${{ number_format(abs($totUtil),0,',','.') }}
                    </td>
                    <td class="right-align" style="color:{{ $totMarg>=0 ? '#388E3C' : '#E53935' }}">{{ $totMarg }}%</td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>

</div>
@endsection

@section('foot')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
(function () {
    var labels  = {!! json_encode(array_values(array_filter(array_map(function($m) use ($resumenAnual, $mesesNombres) {
        return isset($resumenAnual[$m]) && $resumenAnual[$m] !== null ? $mesesNombres[$m] : null;
    }, array_keys($resumenAnual))))) !!};
    var ingresos = {!! json_encode(array_values(array_filter(array_map(function($d) { return $d !== null ? $d['ing'] : null; }, $resumenAnual), function($v){ return $v !== null; }))) !!};
    var egresos  = {!! json_encode(array_values(array_filter(array_map(function($d) { return $d !== null ? $d['egr'] : null; }, $resumenAnual), function($v){ return $v !== null; }))) !!};
    var utilidad = {!! json_encode(array_values(array_filter(array_map(function($d) { return $d !== null ? $d['utilidad'] : null; }, $resumenAnual), function($v){ return $v !== null; }))) !!};

    var ctx = document.getElementById('grafico-utilidad').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Ingresos',
                    data: ingresos,
                    backgroundColor: 'rgba(46,125,50,0.7)',
                    borderRadius: 4,
                    order: 2
                },
                {
                    label: 'Egresos',
                    data: egresos,
                    backgroundColor: 'rgba(183,28,28,0.65)',
                    borderRadius: 4,
                    order: 3
                },
                {
                    label: 'Utilidad',
                    data: utilidad,
                    type: 'line',
                    borderColor: '#1565C0',
                    backgroundColor: 'rgba(21,101,192,0.1)',
                    borderWidth: 2,
                    pointRadius: 4,
                    fill: false,
                    tension: 0.3,
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            var v = ctx.parsed.y;
                            var sign = v < 0 ? '-' : '';
                            return ctx.dataset.label + ': ' + sign + '$' + Math.abs(v).toLocaleString('es-CL');
                        }
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: function(v) {
                            if (Math.abs(v) >= 1000000) return '$' + (v/1000000).toFixed(1) + 'M';
                            if (Math.abs(v) >= 1000) return '$' + (v/1000).toFixed(0) + 'k';
                            return '$' + v;
                        }
                    }
                }
            }
        }
    });
})();
</script>
@endsection
