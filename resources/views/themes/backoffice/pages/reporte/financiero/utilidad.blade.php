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
                Ingresos de la app (con IVA). Utilidad = Ingresos − (Compras netas + Gas + IVA débito + Retención BTE + Sueldos + PPM). El IVA de compras no es gasto: se recupera en el F29. Las remodelaciones se muestran aparte.
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
                <span class="grey-text" style="font-size:.75rem;display:block">Ventas app (con IVA)</span>
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
                                    ['Programas / Abonos', $abonos],
                            ['Gift Cards vendidas', $giftCardsVendidasMes],
                                    ['Consumos (bar/spa)',   $consumos],
                                    ['Servicios Extra',      $servicios],
                                    ['Venta Directa',        $directas],
                                    
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
                            @if($giftCardsVendidasCant > 0 || $giftCardsUsadas > 0)
                            <tr>
                                <td colspan="3" style="padding:6px 0 0;color:#9e9e9e;font-size:.78rem">
                                    @if($giftCardsVendidasCant > 0)Gift Cards vendidas en {{ $nombreMes }}: {{ $giftCardsVendidasCant }} (mismo monto que en Ingresos percibidos).@endif
                                    @if($giftCardsUsadas > 0)Programas canjeados con Gift Card este mes: ${{ number_format($giftCardsUsadas,0,',','.') }}. No vuelven a sumar: ya se contaron cuando se vendio la Gift Card.@endif
                                </td>
                            </tr>
                            @endif
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
                            <a href="{{ route('backoffice.sii.detalleMes', ['anio'=>$anio,'mes'=>$mes]) }}" style="color:#555;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Facturas SII (compras netas)</a>
                            <span>
                                <strong>{{ $facturasSii < 0 ? '-' : '' }}${{ number_format(abs($facturasSii),0,',','.') }}</strong>
                                <span class="grey-text" style="font-size:.8rem;margin-left:6px">{{ $breakdown['facturas'] }}%</span>
                            </span>
                        </div>
                        <div style="background:#e0e0e0;border-radius:4px;height:6px">
                            <div style="background:#EF5350;border-radius:4px;height:6px;width:{{ max(0, min($breakdown['facturas'],100)) }}%"></div>
                        </div>
                        <div style="font-size:.75rem;color:#9e9e9e;margin-top:2px">
                            Neto + exento, menos notas de crédito · IVA crédito ${{ number_format($ivaCompras,0,',','.') }} no es gasto (se descuenta en F29)
                        </div>
                    </div>

                    {{-- Gas y otros sin factura --}}
                    <div style="margin-bottom:14px">
                        <div style="display:flex;justify-content:space-between;font-size:.88rem;margin-bottom:3px">
                            <span style="color:#555">Gas y otros sin factura</span>
                            <span>
                                <strong>${{ number_format($otrosEgresos,0,',','.') }}</strong>
                                <span class="grey-text" style="font-size:.8rem;margin-left:6px">{{ $breakdown['otros'] }}%</span>
                            </span>
                        </div>
                        <div style="background:#e0e0e0;border-radius:4px;height:6px">
                            <div style="background:#8D6E63;border-radius:4px;height:6px;width:{{ min($breakdown['otros'],100) }}%"></div>
                        </div>
                        <div style="font-size:.75rem;color:#9e9e9e;margin-top:2px">
                            Compras con boleta (gas Abastible registrado por el sensor). Sin crédito fiscal.
                        </div>
                    </div>

                    {{-- IVA débito (ventas) --}}
                    <div style="margin-bottom:14px">
                        <div style="display:flex;justify-content:space-between;font-size:.88rem;margin-bottom:3px">
                            <a href="{{ route('backoffice.impuesto.index', ['anio'=>$anio,'mes'=>$mes]) }}" style="color:#555;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">IVA ventas (débito fiscal)</a>
                            <span>
                                <strong>${{ number_format($ivaDebito,0,',','.') }}</strong>
                                <span class="grey-text" style="font-size:.8rem;margin-left:6px">{{ $breakdown['iva'] }}%</span>
                            </span>
                        </div>
                        <div style="background:#e0e0e0;border-radius:4px;height:6px">
                            <div style="background:#7E57C2;border-radius:4px;height:6px;width:{{ min($breakdown['iva'],100) }}%"></div>
                        </div>
                        @if($ivaEstimado)
                        <div style="font-size:.75rem;color:#FF8F00;margin-top:2px">
                            <i class="material-icons" style="font-size:.8rem;vertical-align:middle">warning</i>
                            Estimado (19/119 de los ingresos). Sincroniza ventas SII en <a href="{{ route('backoffice.impuesto.index', ['anio'=>$anio,'mes'=>$mes]) }}">F29 Estimado</a> para usar el dato real.
                        </div>
                        @else
                        <div style="font-size:.75rem;color:#9e9e9e;margin-top:2px">
                            IVA cobrado a clientes, se entrega al SII. En caja: crédito a proveedores + F29 (débito − crédito).
                        </div>
                        @endif
                    </div>

                    {{-- Retención Honorarios --}}
                    <div style="margin-bottom:14px">
                        <div style="display:flex;justify-content:space-between;font-size:.88rem;margin-bottom:3px">
                            <a href="{{ route('backoffice.honorarios.index') }}" style="color:#555;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Retención honorarios (SII)</a>
                            <span>
                                <strong>${{ number_format($honorariosRetencion,0,',','.') }}</strong>
                                <span class="grey-text" style="font-size:.8rem;margin-left:6px">{{ $breakdown['honorarios'] }}%</span>
                            </span>
                        </div>
                        <div style="background:#e0e0e0;border-radius:4px;height:6px">
                            <div style="background:#FF7043;border-radius:4px;height:6px;width:{{ min($breakdown['honorarios'],100) }}%"></div>
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
                                <span class="grey-text" style="font-size:.8rem;margin-left:6px">{{ $breakdown['sueldos'] }}%</span>
                            </span>
                        </div>
                        <div style="background:#e0e0e0;border-radius:4px;height:6px">
                            <div style="background:#26A69A;border-radius:4px;height:6px;width:{{ min($breakdown['sueldos'],100) }}%"></div>
                        </div>
                        <div style="font-size:.75rem;color:#9e9e9e;margin-top:2px">
                            Devengado (sueldo, BTE neto, comisiones y bonos) · sin propinas (${{ number_format($propinas,0,',','.') }}, las paga el cliente aparte)
                        </div>
                    </div>

                    {{-- F29 PPM --}}
                    <div style="margin-bottom:14px">
                        <div style="display:flex;justify-content:space-between;font-size:.88rem;margin-bottom:3px">
                            <a href="{{ route('backoffice.impuesto.index') }}" style="color:#555;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">F29 PPM (0.125% × ventas sin IVA)</a>
                            <span>
                                <strong>${{ number_format($ppm,0,',','.') }}</strong>
                                <span class="grey-text" style="font-size:.8rem;margin-left:6px">{{ $breakdown['ppm'] }}%</span>
                            </span>
                        </div>
                        <div style="background:#e0e0e0;border-radius:4px;height:6px">
                            <div style="background:#AB47BC;border-radius:4px;height:6px;width:{{ min($breakdown['ppm'],100) }}%"></div>
                        </div>
                        <div style="font-size:.75rem;color:{{ $ivaEstimado ? '#FF8F00' : '#9e9e9e' }};margin-top:2px">
                            Base ${{ number_format($basePpm,0,',','.') }}{{ $ivaEstimado ? ' (estimada, sin sync SII)' : ' (ventas SII netas + exentas)' }}
                        </div>
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
                @if($inversiones != 0)
                <div style="display:flex;justify-content:space-between;font-size:.85rem;margin-top:8px;color:#555">
                    <span>− Inversión / remodelación (aparte, no es gasto del mes)</span>
                    <span>${{ number_format($inversiones,0,',','.') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:.9rem;font-weight:700;margin-top:4px;color:{{ $utilidadTrasInversion >= 0 ? '#1565C0' : '#B71C1C' }}">
                    <span>Resultado después de inversión</span>
                    <span>{{ $utilidadTrasInversion < 0 ? '-' : '' }}${{ number_format(abs($utilidadTrasInversion),0,',','.') }}</span>
                </div>
                @endif
            </div>
        </div>

    </div>

    <div class="card" style="border-radius:8px;margin-top:12px;border-left:4px solid #6A1B9A">
        <div class="card-content" style="padding:16px 20px">
            <p class="grey-text text-darken-2" style="font-weight:600;margin:0 0 4px;font-size:.95rem">
                <i class="material-icons tiny">receipt_long</i> IVA segun SII (oficial) &mdash; {{ ucfirst($nombreMes) }}
            </p>
            <p class="grey-text" style="margin:0 0 12px;font-size:.78rem">
                Dato exacto del RCV sincronizado con el SII. Es la antesala de lo que hay que declarar/pagar en el F29. El IVA débito de aquí es el que se resta arriba en la Utilidad. El crédito sigue el periodo de declaración SII, por eso puede diferir del IVA de las facturas del mes.
                @if($ultimaSyncSii)
                    <br>Ultima sincronizacion: {{ $ultimaSyncSii->format('d/m/Y H:i') }}
                @else
                    <br><span class="orange-text">Sin sincronizar este periodo &mdash; ve a <a href="{{ route('backoffice.impuesto.index', ['anio'=>$anio,'mes'=>$mes]) }}">F29 Estimado</a> y presiona "Sincronizar Ventas SII".</span>
                @endif
            </p>
            <div class="row" style="margin-bottom:0">
                <div class="col s6 m3">
                    <span class="grey-text" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;display:block">IVA Debito (ventas)</span>
                    <span style="font-size:1.1rem;font-weight:700;color:#C62828">${{ number_format($ivaDebitoSii,0,',','.') }}</span>
                </div>
                <div class="col s6 m3">
                    <span class="grey-text" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;display:block">IVA Credito (compras)</span>
                    <span style="font-size:1.1rem;font-weight:700;color:#388E3C">${{ number_format($ivaCreditoSii,0,',','.') }}</span>
                </div>
                <div class="col s6 m3">
                    <span class="grey-text" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;display:block">Remanente mes anterior</span>
                    @if($remanenteAntSii !== null)
                        <span style="font-size:1.1rem;font-weight:700;color:#388E3C">${{ number_format($remanenteAntSii,0,',','.') }}</span>
                    @else
                        <span style="font-size:.9rem;color:#FF8F00">Desconocido</span>
                    @endif
                </div>
                <div class="col s6 m3">
                    <span class="grey-text" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;display:block">IVA a pagar (Codigo 089)</span>
                    <span style="font-size:1.2rem;font-weight:700;color:{{ $ivaAPagarSii > 0 ? '#B71C1C' : '#555' }}">${{ number_format($ivaAPagarSii,0,',','.') }}</span>
                    @if($ivaRemanenteSii > 0)
                        <span style="font-size:.75rem;display:block;color:#2E7D32">Remanente siguiente ${{ number_format($ivaRemanenteSii,0,',','.') }}</span>
                    @endif
                </div>
            </div>
<div class="row" style="margin:12px 0 0;padding-top:12px;border-top:1px dashed #d1c4e9">
    <div class="col s6 m3">
        <span class="grey-text" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;display:block">Retencion honorarios (Codigo 151)</span>
        <span style="font-size:1.1rem;font-weight:700;color:#B71C1C">${{ number_format($honorariosRetencion,0,',','.') }}</span>
    </div>
    <div class="col s6 m3">
        <span class="grey-text" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;display:block">PPM (Codigo 062)</span>
        <span style="font-size:1.1rem;font-weight:700;color:#B71C1C">${{ number_format($ppm,0,',','.') }}</span>
        @if($ivaEstimado)<span style="font-size:.75rem;display:block;color:#FF8F00">Estimado (sin sync SII)</span>@endif
    </div>
    <div class="col s12 m6">
        <span class="grey-text" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;display:block">Total a pagar F29 (IVA + retenciones + PPM)</span>
        <span style="font-size:1.4rem;font-weight:700;color:#B71C1C">${{ number_format($totalF29,0,',','.') }}</span>
        <span style="font-size:.75rem;display:block;color:#9e9e9e">${{ number_format($ivaAPagarSii,0,',','.') }} + ${{ number_format($honorariosRetencion,0,',','.') }} + ${{ number_format($ppm,0,',','.') }}</span>
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
                    <th class="right-align">Inversión (aparte)</th>
                </tr>
            </thead>
            <tbody>
                @php $totIng=0; $totEgr=0; $totInv=0; @endphp
                @foreach($resumenAnual as $m => $d)
                @php $esMes = ($m == $mes); @endphp
                <tr class="{{ $esMes ? 'blue lighten-5' : '' }}">
                    <td>
                        <a href="{{ route('backoffice.finanzas.utilidad', ['anio'=>$anio,'mes'=>$m]) }}"
                           class="{{ $esMes ? 'blue-text font-weight-bold' : '' }}">
                            {{ $mesesNombres[$m] }}
                        </a>
                        @if($d !== null && $d['iva_estimado'])
                            <span class="orange-text" title="IVA y PPM estimados: mes sin sincronizar con el SII">*</span>
                        @endif
                    </td>
                    @if($d === null)
                        <td class="right-align grey-text" colspan="5">—</td>
                    @else
                    @php $totIng += $d['ing']; $totEgr += $d['egr']; $totInv += $d['inversion']; @endphp
                    <td class="right-align green-text text-darken-2">${{ number_format($d['ing'],0,',','.') }}</td>
                    <td class="right-align red-text">${{ number_format($d['egr'],0,',','.') }}</td>
                    <td class="right-align" style="color:{{ $d['utilidad']>=0 ? '#1565C0' : '#E53935' }};font-weight:600">
                        {{ $d['utilidad']>=0 ? '' : '-' }}${{ number_format(abs($d['utilidad']),0,',','.') }}
                    </td>
                    <td class="right-align" style="color:{{ $d['margen']>=0 ? '#388E3C' : '#E53935' }}">
                        {{ $d['margen'] }}%
                    </td>
                    <td class="right-align grey-text">
                        {{ $d['inversion'] ? '$' . number_format($d['inversion'],0,',','.') : '—' }}
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
                    <td class="right-align grey-text">${{ number_format($totInv,0,',','.') }}</td>
                </tr>
            </tfoot>
        </table>
        </div>
        <p class="grey-text" style="font-size:.75rem;margin:4px 0 0">
            <span class="orange-text">*</span> Mes sin sincronizar con el SII: IVA débito y PPM estimados (19/119 de los ingresos).
        </p>
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
