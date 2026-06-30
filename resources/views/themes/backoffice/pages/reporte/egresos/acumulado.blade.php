@extends('themes.backoffice.layouts.admin')

@section('title', 'Egresos – ' . ucfirst($mesNombre) . ' ' . $anio)

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
@endsection

@section('breadcrumbs')
<li><a href="{{ route('backoffice.finanzas.egresos.acumulado') }}">Egresos</a></li>
<li>{{ ucfirst($mesNombre) }} {{ $anio }}</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.finanzas.resumen.anual') }}" class="grey-text text-darken-2">Resumen Anual</a></li>
<li><a href="{{ route('backoffice.finanzas.ingresos_percibidos') }}" class="grey-text text-darken-2">Ingresos</a></li>
<li><a href="{{ route('backoffice.honorarios.index') }}" class="grey-text text-darken-2">Honorarios BTE</a></li>
@endsection

@section('content')
<div class="section">

    {{-- ── Selector de período ───────────────────────────────────────────── --}}
    <div class="row" style="margin-bottom:0;align-items:center;display:flex;flex-wrap:wrap;gap:12px">
        <div class="col" style="flex:1;min-width:200px">
            <h5 class="grey-text text-darken-2" style="margin:0">
                <i class="material-icons left" style="font-size:1.4rem;line-height:1.6rem">bar_chart</i>
                Egresos — {{ ucfirst($mesNombre) }} {{ $anio }}
            </h5>
        </div>
        <div class="col" style="display:flex;gap:8px;align-items:center">
            @php
                $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            @endphp
            <select id="periodoSelect" class="browser-default"
                style="border:1px solid #ccc;border-radius:4px;padding:4px 8px;min-width:150px">
                @foreach(range(date('Y'), 2025) as $a)
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ route('backoffice.finanzas.egresos.mes', [$a, $m]) }}"
                            {{ ($mes == $m && $anio == $a) ? 'selected' : '' }}>
                            {{ $meses[$m] }} {{ $a }}
                        </option>
                    @endfor
                @endforeach
            </select>
        </div>
    </div>

    {{-- ── Tarjetas resumen ──────────────────────────────────────────────── --}}
    @php
        $totalConBte     = $totalGeneral + $totalBte + $totalSueldos;
        $varPct          = $totalAnterior > 0 ? round((($totalConBte - ($totalAnterior + $totalBte + $totalSueldos)) / ($totalAnterior + $totalBte + $totalSueldos)) * 100, 1) : null;
    @endphp
    <div class="row" style="margin-top:20px;margin-bottom:8px">
        <div class="col s6 m3">
            <div class="card-panel center-align" style="padding:12px 8px;margin:4px 0">
                <span class="grey-text" style="font-size:.75rem;display:block">Total Egresos</span>
                <span style="font-size:1.4rem;font-weight:700;color:#d32f2f">${{ number_format($totalGeneral,0,',','.') }}</span>
            </div>
        </div>
        <div class="col s6 m3">
            <div class="card-panel center-align" style="padding:12px 8px;margin:4px 0">
                <span class="grey-text" style="font-size:.75rem;display:block">Honorarios BTE</span>
                <span style="font-size:1.4rem;font-weight:700;color:#f57c00">${{ number_format($totalBte,0,',','.') }}</span>
            </div>
        </div>
        <div class="col s6 m3">
            <div class="card-panel center-align" style="padding:12px 8px;margin:4px 0">
                <span class="grey-text" style="font-size:.75rem;display:block">Sueldos</span>
                <span style="font-size:1.4rem;font-weight:700;color:#1565c0">${{ number_format($totalSueldos,0,',','.') }}</span>
            </div>
        </div>
        <div class="col s6 m3">
            <div class="card-panel center-align" style="padding:12px 8px;margin:4px 0">
                <span class="grey-text" style="font-size:.75rem;display:block">Total consolidado</span>
                <span style="font-size:1.4rem;font-weight:700;color:#333">${{ number_format($totalConBte,0,',','.') }}</span>
                @if($varPct !== null)
                    <span style="font-size:.75rem;color:{{ $varPct > 0 ? '#c62828' : '#2e7d32' }}">
                        {{ $varPct > 0 ? '▲' : '▼' }} {{ abs($varPct) }}% vs mes ant.
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Layout: gráfico + resumen anual ─────────────────────────────── --}}
    <div class="row">
        <div class="col s12 m5 l4">
            <div class="card" style="border-radius:8px;padding:16px">
                <p class="grey-text" style="margin:0 0 8px;font-size:.85rem;font-weight:600">Distribución {{ ucfirst($mesNombre) }}</p>
                @if(count($chartData) > 0 && array_sum($chartData) > 0)
                <canvas id="chartEgresos" style="max-height:260px"></canvas>
                @else
                <div class="center-align grey-text" style="padding:40px 0">
                    <i class="material-icons" style="font-size:2rem">bar_chart</i>
                    <p>Sin egresos registrados</p>
                </div>
                @endif
            </div>
        </div>
        <div class="col s12 m7 l8">
            <div class="card" style="border-radius:8px;padding:0">
                <div class="card-content" style="padding:12px 16px">
                    <p class="grey-text" style="margin:0 0 8px;font-size:.85rem;font-weight:600">Resumen {{ $anio }}</p>
                    <table class="striped" style="font-size:.88rem">
                        <thead>
                            <tr style="background:#eceff1">
                                <th style="padding:6px 8px">Mes</th>
                                <th class="right-align" style="padding:6px 8px">Egresos</th>
                                <th class="right-align" style="padding:6px 8px;color:#f57c00">BHE</th>
                                <th class="right-align" style="padding:6px 8px;color:#1565c0">Sueldos</th>
                                <th class="right-align" style="padding:6px 8px">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalAnualEgresos = $resumenAnual->sum();
                                $totalAnualBte     = $bteAnual->sum();
                                $totalAnualSueldos = $sueldosAnual->sum();
                            @endphp
                            @foreach($meses as $mn => $mNombre)
                                @if($mn === 0) @continue @endif
                                @php
                                    $vEg  = (int) ($resumenAnual[$mn] ?? 0);
                                    $vBte = (int) ($bteAnual[$mn] ?? 0);
                                    $vSu  = (int) ($sueldosAnual[$mn] ?? 0);
                                    $vTot = $vEg + $vBte + $vSu;
                                    $esActual = ($mn == $mes);
                                @endphp
                                <tr class="{{ $esActual ? 'blue lighten-5' : '' }}">
                                    <td style="padding:4px 8px">
                                        <a href="{{ route('backoffice.finanzas.egresos.mes', [$anio, $mn]) }}"
                                           class="{{ $esActual ? 'blue-text font-weight-bold' : '' }}">
                                            {{ $mNombre }}
                                        </a>
                                    </td>
                                    <td class="right-align" style="padding:4px 8px;font-family:monospace;color:#c62828">
                                        @if($vEg > 0) ${{ number_format($vEg,0,',','.') }} @else — @endif
                                    </td>
                                    <td class="right-align" style="padding:4px 8px;font-family:monospace;color:#f57c00">
                                        @if($vBte > 0) ${{ number_format($vBte,0,',','.') }} @else — @endif
                                    </td>
                                    <td class="right-align" style="padding:4px 8px;font-family:monospace;color:#1565c0">
                                        @if($vSu > 0) ${{ number_format($vSu,0,',','.') }} @else — @endif
                                    </td>
                                    <td class="right-align" style="padding:4px 8px;font-family:monospace;font-weight:{{ $vTot > 0 ? '700' : '400' }}">
                                        @if($vTot > 0) ${{ number_format($vTot,0,',','.') }} @else — @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:#eceff1;font-weight:700">
                                <td style="padding:6px 8px">Total {{ $anio }}</td>
                                <td class="right-align" style="padding:6px 8px;color:#c62828">
                                    @if($totalAnualEgresos > 0) ${{ number_format($totalAnualEgresos,0,',','.') }} @else — @endif
                                </td>
                                <td class="right-align" style="padding:6px 8px;color:#f57c00">
                                    @if($totalAnualBte > 0) ${{ number_format($totalAnualBte,0,',','.') }} @else — @endif
                                </td>
                                <td class="right-align" style="padding:6px 8px;color:#1565c0">
                                    @if($totalAnualSueldos > 0) ${{ number_format($totalAnualSueldos,0,',','.') }} @else — @endif
                                </td>
                                <td class="right-align" style="padding:6px 8px">
                                    ${{ number_format($totalAnualEgresos + $totalAnualBte + $totalAnualSueldos,0,',','.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Acumulado por categoría / subcategoría ────────────────────────── --}}
    @if(empty($agrupado))
    <div class="card-panel grey lighten-4 center-align">
        <i class="material-icons grey-text" style="font-size:3rem;display:block">inbox</i>
        <p class="grey-text">No hay egresos registrados para {{ ucfirst($mesNombre) }} {{ $anio }}.</p>
        <a href="{{ route('backoffice.egreso.create') }}" class="btn teal waves-effect">
            <i class="material-icons left">add</i> Registrar egreso
        </a>
    </div>
    @else

    @php
        $coloresCat = ['teal','indigo','deep-orange','purple','blue-grey','cyan','brown','green'];
        $ci = 0;
    @endphp

    @foreach($agrupado as $catNombre => $catDatos)
    @php
        $color = $coloresCat[$ci % count($coloresCat)];
        $pctCat = $totalGeneral > 0 ? round($catDatos['total'] / $totalGeneral * 100, 1) : 0;
        $antCat = (int) ($anteriores[$catNombre] ?? 0);
        $ci++;
    @endphp
    <div class="card" style="border-radius:8px;overflow:hidden;margin-bottom:16px">
        {{-- Header categoría --}}
        <div class="card-content {{ $color }} lighten-5" style="padding:10px 16px;border-bottom:3px solid;border-color:currentColor;cursor:pointer"
             onclick="toggleCat('cat_{{ $ci }}')">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span style="font-weight:700;font-size:1rem;color:#37474f">
                    <i class="material-icons tiny" style="vertical-align:middle">folder</i>
                    {{ $catNombre }}
                </span>
                <span>
                    <span class="grey-text text-darken-1" style="font-size:.8rem;margin-right:12px">
                        {{ $pctCat }}% del total
                        @if($antCat > 0)
                            &nbsp;|&nbsp;
                            @php $dif = $catDatos['total'] - $antCat; @endphp
                            <span style="color:{{ $dif > 0 ? '#c62828' : '#2e7d32' }}">
                                {{ $dif > 0 ? '▲' : '▼' }} ${{ number_format(abs($dif),0,',','.') }}
                            </span> vs ant.
                        @endif
                    </span>
                    <strong style="font-size:1.1rem;color:#c62828">${{ number_format($catDatos['total'],0,',','.') }}</strong>
                    <i class="material-icons tiny" style="vertical-align:middle;color:#78909c" id="icon_cat_{{ $ci }}">expand_more</i>
                </span>
            </div>
        </div>

        {{-- Subcategorías --}}
        <div id="cat_{{ $ci }}" style="padding:0">
            @foreach($catDatos['subcategorias'] as $subNombre => $subDatos)
            @php
                $pctSub = $catDatos['total'] > 0 ? round($subDatos['total'] / $catDatos['total'] * 100, 1) : 0;
            @endphp
            <div style="border-bottom:1px solid #e0e0e0">
                {{-- Sub-header --}}
                <div style="padding:8px 16px;background:#fafafa;display:flex;justify-content:space-between;align-items:center;cursor:pointer"
                     onclick="toggleSub('sub_{{ $ci }}_{{ $loop->index }}')">
                    <span style="color:#546e7a;font-size:.9rem;font-weight:600">
                        <i class="material-icons tiny" style="vertical-align:middle;color:#90a4ae">subdirectory_arrow_right</i>
                        {{ $subNombre }}
                        <span class="grey-text" style="font-size:.78rem;font-weight:400;margin-left:4px">({{ count($subDatos['filas']) }} doc.)</span>
                    </span>
                    <span>
                        <span class="grey-text" style="font-size:.78rem;margin-right:8px">{{ $pctSub }}%</span>
                        <strong style="color:#455a64">${{ number_format($subDatos['total'],0,',','.') }}</strong>
                        <i class="material-icons tiny" style="vertical-align:middle;color:#b0bec5" id="icon_sub_{{ $ci }}_{{ $loop->index }}">expand_more</i>
                    </span>
                </div>

                {{-- Filas individuales --}}
                <div id="sub_{{ $ci }}_{{ $loop->index }}" style="display:none">
                    <table class="striped" style="margin:0;font-size:.84rem">
                        <thead>
                            <tr style="background:#f5f5f5">
                                <th style="padding:6px 16px">Fecha</th>
                                <th style="padding:6px 8px">Proveedor / Descripción</th>
                                <th style="padding:6px 8px">N° Doc.</th>
                                <th style="padding:6px 8px">Fuente</th>
                                <th class="right-align" style="padding:6px 8px">Neto</th>
                                <th class="right-align" style="padding:6px 8px">IVA</th>
                                <th class="right-align" style="padding:6px 16px">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subDatos['filas'] as $fila)
                            <tr>
                                <td style="padding:5px 16px;white-space:nowrap">{{ $fila->fecha_egreso }}</td>
                                <td style="padding:5px 8px">
                                    {{ $fila->proveedor_nombre ?? $fila->descripcion ?? '—' }}
                                    @if($fila->proveedor_nombre && $fila->descripcion)
                                        <br><small class="grey-text">{{ $fila->descripcion }}</small>
                                    @endif
                                </td>
                                <td style="padding:5px 8px;font-family:monospace;color:#607d8b">{{ $fila->numero_documento ?: '—' }}</td>
                                <td style="padding:5px 8px">
                                    @php
                                        $fuenteColores = ['sii'=>'teal','manual'=>'grey','home_assistant'=>'blue','scan'=>'orange','woocommerce'=>'purple'];
                                        $fc = $fuenteColores[$fila->fuente] ?? 'grey';
                                    @endphp
                                    <span class="badge {{ $fc }} white-text" style="border-radius:4px;font-size:.7rem;padding:2px 6px;position:static">
                                        {{ strtoupper($fila->fuente ?? 'manual') }}
                                    </span>
                                </td>
                                <td class="right-align" style="padding:5px 8px">
                                    @if($fila->neto) ${{ number_format($fila->neto,0,',','.') }} @else — @endif
                                </td>
                                <td class="right-align" style="padding:5px 8px">
                                    @if($fila->iva) ${{ number_format($fila->iva,0,',','.') }} @else — @endif
                                </td>
                                <td class="right-align" style="padding:5px 16px;font-weight:600">
                                    ${{ number_format($fila->total ?? $fila->neto ?? 0,0,',','.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:#f5f5f5">
                                <td colspan="6" style="padding:5px 8px;text-align:right;font-weight:600;color:#546e7a">Subtotal {{ $subNombre }}</td>
                                <td class="right-align" style="padding:5px 16px;font-weight:700;color:#c62828">${{ number_format($subDatos['total'],0,',','.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endforeach

            {{-- Total categoría --}}
            <div style="padding:10px 16px;background:#eceff1;display:flex;justify-content:flex-end;align-items:center;gap:24px">
                @if($antCat > 0)
                <span class="grey-text" style="font-size:.83rem">
                    Mes anterior: ${{ number_format($antCat,0,',','.') }}
                </span>
                @endif
                <span style="font-size:1rem;font-weight:700;color:#b71c1c">
                    Total {{ $catNombre }}: ${{ number_format($catDatos['total'],0,',','.') }}
                </span>
            </div>
        </div>
    </div>
    @endforeach

    {{-- ── Total general ────────────────────────────────────────────────── --}}
    <div class="card-panel" style="background:#263238;border-radius:8px;padding:16px 24px">
        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;color:#fff">
            <div>
                <span style="font-size:.8rem;opacity:.7">Egresos registrados</span><br>
                <span style="font-size:1.2rem;font-weight:700">${{ number_format($totalGeneral,0,',','.') }}</span>
            </div>
            @if($totalBte > 0)
            <div>
                <span style="font-size:.8rem;opacity:.7">Honorarios BTE</span><br>
                <span style="font-size:1.2rem;font-weight:700;color:#ffcc02">${{ number_format($totalBte,0,',','.') }}</span>
            </div>
            @endif
            @if($totalSueldos > 0)
            <div>
                <span style="font-size:.8rem;opacity:.7">Sueldos</span><br>
                <span style="font-size:1.2rem;font-weight:700;color:#80cbc4">${{ number_format($totalSueldos,0,',','.') }}</span>
            </div>
            @endif
            <div style="border-left:1px solid rgba(255,255,255,.2);padding-left:24px">
                <span style="font-size:.8rem;opacity:.7">TOTAL CONSOLIDADO</span><br>
                <span style="font-size:1.6rem;font-weight:700;color:#ef5350">${{ number_format($totalConBte,0,',','.') }}</span>
                @if($varPct !== null)
                <br><span style="font-size:.78rem;opacity:.8">{{ $varPct > 0 ? '▲' : '▼' }} {{ abs($varPct) }}% vs mes anterior</span>
                @endif
            </div>
        </div>
    </div>

    @endif {{-- /empty agrupado --}}

</div>
@endsection

@section('scripts')
<script>
function toggleCat(id) {
   