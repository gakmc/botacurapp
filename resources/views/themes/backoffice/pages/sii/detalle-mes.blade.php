@extends('themes.backoffice.layouts.admin')

@section('title', 'Detalle SII – ' . $nombreMes)

@section('breadcrumbs')
<li><a href="{{ route('backoffice.egreso.index') }}">Egresos</a></li>
<li><a href="{{ route('backoffice.sii.resumen') }}">Resumen SII</a></li>
<li>{{ $nombreMes }}</li>
@endsection

@section('content')
<div class="section">

    {{-- HEADER TOTALES --}}
    <div class="card-panel" style="padding:16px 24px;">
        <div class="row valign-wrapper" style="margin:0;">
            <div class="col s12 m9">
                <h6 style="margin:0 0 6px; font-weight:700;">Facturas SII importadas — {{ $nombreMes }}</h6>
                <div style="font-size:13px; display:flex; gap:24px; flex-wrap:wrap;">
                    <span class="grey-text">
                        <strong>{{ $egresos->count() }}</strong> documento(s)
                    </span>
                    <span>
                        Neto: <strong>${{ number_format($egresos->sum('neto'), 0, ',', '.') }}</strong>
                    </span>
                    <span>
                        IVA: <strong>${{ number_format($egresos->sum('iva'), 0, ',', '.') }}</strong>
                    </span>
                    <span>
                        Total: <strong style="color:#039B7B; font-size:15px;">
                            ${{ number_format($egresos->sum('total'), 0, ',', '.') }}
                        </strong>
                    </span>
                </div>
            </div>
            <div class="col s12 m3 right-align">
                <a href="{{ route('backoffice.sii.listar', ['mes' => $mes, 'anio' => $anio]) }}"
                   class="btn-flat btn-small waves-effect grey-text" style="font-size:12px;">
                    <i class="material-icons tiny left">open_in_new</i>Ver en SII
                </a>
            </div>
        </div>
    </div>

    {{-- TOTALES POR PROVEEDOR --}}
    <div class="card-panel" style="padding:16px 24px;">
        <h6 style="margin:0 0 12px; font-weight:700;">Totales por Proveedor</h6>
        <table class="striped responsive-table" style="font-size:13px;">
            <thead>
                <tr style="background:#eceff1;">
                    <th>Proveedor</th>
                    <th>RUT</th>
                    <th class="center">Docs</th>
                    <th class="right-align">Neto</th>
                    <th class="right-align">IVA</th>
                    <th class="right-align" style="color:#1b5e20;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($porProveedor as $p)
                <tr>
                    <td>{{ $p['razon_social'] }}</td>
                    <td><small class="grey-text">{{ $p['rut'] }}</small></td>
                    <td class="center">{{ $p['docs'] }}</td>
                    <td class="right-align">${{ number_format($p['neto'], 0, ',', '.') }}</td>
                    <td class="right-align">${{ number_format($p['iva'], 0, ',', '.') }}</td>
                    <td class="right-align" style="font-weight:700; color:#1b5e20;">${{ number_format($p['total'], 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="grey-text center" style="padding:20px;">Sin datos</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background:#e8f5e9; font-weight:700;">
                    <td colspan="2">TOTAL {{ $nombreMes }}</td>
                    <td class="center">{{ $egresos->count() }}</td>
                    <td class="right-align">${{ number_format($egresos->sum('neto'), 0, ',', '.') }}</td>
                    <td class="right-align">${{ number_format($egresos->sum('iva'), 0, ',', '.') }}</td>
                    <td class="right-align" style="color:#1b5e20;">${{ number_format($egresos->sum('total'), 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- DETALLE POR FACTURA --}}
    <div class="card-panel" style="padding:16px 24px;">
        <h6 style="margin:0 0 12px; font-weight:700;">Detalle por Factura</h6>
        <table class="striped responsive-table" style="font-size:12px;">
            <thead>
                <tr style="background:#eceff1;">
                    <th style="width:100px;">Fecha</th>
                    <th style="width:80px;">Folio</th>
                    <th>Proveedor</th>
                    <th>Categoría</th>
                    <th class="right-align">Neto</th>
                    <th class="right-align">IVA</th>
                    <th class="right-align">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($egresos as $e)
                <tr>
                    <td style="white-space:nowrap;">
                        @if($e->fecha_egreso)
                            {{ \Carbon\Carbon::parse($e->fecha_egreso)->format('d-m-Y') }}
                        @else
                            <span class="grey-text">—</span>
                        @endif
                    </td>
                    <td>{{ $e->folio }}</td>
                    <td style="max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                        title="{{ $e->descripcion }}">
                        {{ preg_replace('/ - Folio .+/', '', $e->descripcion) }}
                    </td>
                    <td><small class="grey-text">{{ $e->subcategoria ?? $e->categoria ?? '—' }}</small></td>
                    <td class="right-align">${{ number_format($e->neto ?: 0, 0, ',', '.') }}</td>
                    <td class="right-align">${{ number_format($e->iva  ?: 0, 0, ',', '.') }}</td>
                    <td class="right-align"><strong>${{ number_format($e->total, 0, ',', '.') }}</strong></td>
                </tr>
                @empty
                <tr><td colspan="7" class="grey-text center" style="padding:20px;">Sin documentos</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
