@extends('themes.backoffice.layouts.admin')

@section('title', 'Documentos SII – ' . $nombreMes)

@section('breadcrumbs')
<li><a href="{{ route('backoffice.egreso.index') }}">Egresos</a></li>
<li><a href="{{ route('backoffice.sii.index') }}">Importar desde SII</a></li>
<li>{{ $nombreMes }}</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.sii.index') }}" class="grey-text text-darken-2">Cambiar período</a></li>
@endsection

@section('content')
<div class="section">

    {{-- Mensajes flash --}}
    @if(session('success'))
    <div class="card-panel green lighten-4" style="margin-bottom: 16px;">
        <span class="green-text text-darken-2">
            <i class="material-icons tiny">check_circle</i> {{ session('success') }}
        </span>
    </div>
    @endif
    @if(session('error'))
    <div class="card-panel red lighten-4" style="margin-bottom: 16px;">
        <span class="red-text text-darken-2">
            <i class="material-icons tiny">error</i> {{ session('error') }}
        </span>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- BLOQUE 1: RESUMEN POR PROVEEDOR                           --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="card-panel">
        <h6 style="margin: 0 0 4px; font-weight: 600;">
            <i class="material-icons tiny">receipt_long</i>
            Facturas de Compra – {{ $nombreMes }}
        </h6>
        <p class="grey-text" style="margin: 0 0 16px; font-size: 13px;">
            {{ $documentos->count() }} documento(s) ·
            {{ $documentos->where('ya_importado', false)->count() }} pendiente(s) de importar ·
            {{ $documentos->where('ya_importado', true)->count() }} ya importado(s)
        </p>

        @if($documentos->isEmpty())
        <p class="center grey-text">No se encontraron documentos para este período.</p>
        @else

        {{-- Tabla resumen por proveedor --}}
        <table class="responsive-table striped" style="font-size: 13px;">
            <thead>
                <tr style="background: #eceff1;">
                    <th>Proveedor</th>
                    <th>RUT</th>
                    <th class="center">Docs</th>
                    <th class="center" style="color: #e65100;">Pendientes</th>
                    <th class="right-align">Neto</th>
                    <th class="right-align">IVA</th>
                    <th class="right-align" style="color: #1b5e20;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($totalesPorProveedor as $prov)
                <tr>
                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{ $prov['razon_social'] }}
                    </td>
                    <td><small class="grey-text">{{ $prov['rut_emisor'] }}</small></td>
                    <td class="center">{{ $prov['cant'] }}</td>
                    <td class="center">
                        @if($prov['pendientes'] > 0)
                            <span style="color: #e65100; font-weight: 600;">{{ $prov['pendientes'] }}</span>
                        @else
                            <span class="green-text">✓</span>
                        @endif
                    </td>
                    <td class="right-align">
                        @if($prov['neto'])
                            ${{ number_format($prov['neto'], 0, ',', '.') }}
                        @else
                            <span class="grey-text">—</span>
                        @endif
                    </td>
                    <td class="right-align">
                        @if($prov['iva'])
                            ${{ number_format($prov['iva'], 0, ',', '.') }}
                        @else
                            <span class="grey-text">—</span>
                        @endif
                    </td>
                    <td class="right-align" style="font-weight: 600; color: #1b5e20;">
                        ${{ number_format($prov['total'], 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: #e8f5e9; font-weight: 700;">
                    <td colspan="2"><strong>TOTAL {{ $nombreMes }}</strong></td>
                    <td class="center">{{ $documentos->count() }}</td>
                    <td class="center" style="color: #e65100;">{{ $documentos->where('ya_importado', false)->count() }}</td>
                    <td class="right-align">${{ number_format($documentos->sum('monto_neto'), 0, ',', '.') }}</td>
                    <td class="right-align">${{ number_format($documentos->sum('monto_iva'), 0, ',', '.') }}</td>
                    <td class="right-align" style="color: #1b5e20;">${{ number_format($documentos->sum('monto_total'), 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- BLOQUE 2: IMPORTAR TODO EL PERÍODO                        --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    @if($documentos->where('ya_importado', false)->count() > 0)
    <div class="card-panel" style="border-left: 4px solid #039B7B;">
        <h6 style="margin: 0 0 12px; font-weight: 600;">
            <i class="material-icons tiny" style="color: #039B7B;">cloud_download</i>
            Importar {{ $documentos->where('ya_importado', false)->count() }} documento(s) pendientes
        </h6>
        <p class="grey-text" style="font-size: 13px; margin: 0 0 16px;">
            Selecciona la categoría por defecto para todos los documentos. Puedes cambiarla individualmente después desde Egresos.
        </p>

        <form action="{{ route('backoffice.sii.importarTodo') }}" method="POST" id="form-importar-todo">
            @csrf
            <input type="hidden" name="anio" value="{{ $anio }}">
            <input type="hidden" name="mes"  value="{{ $mes }}">

            <div class="row" style="margin-bottom: 0; align-items: flex-end;">
                <div class="col s12 m4">
                    <label class="grey-text" style="font-size: 12px; display:block; margin-bottom:4px;">Categoría</label>
                    <select name="categoria_id" id="cat-todo" class="browser-default" style="border:1px solid #bdbdbd; border-radius:4px; padding:8px 10px; width:100%; background:#fff;" required>
                        <option value="" disabled selected>-- Selecciona categoría --</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col s12 m4">
                    <label class="grey-text" style="font-size: 12px; display:block; margin-bottom:4px;">Subcategoría</label>
                    <select name="subcategoria_id" id="subcat-todo" class="browser-default" style="border:1px solid #bdbdbd; border-radius:4px; padding:8px 10px; width:100%; background:#fff;" required>
                        <option value="" disabled selected>-- Primero selecciona categoría --</option>
                    </select>
                </div>
                <div class="col s12 m4" style="padding-top: 16px;">
                    <button type="submit" id="btn-importar-todo" class="btn waves-effect" style="background-color: #039B7B; width: 100%;" disabled>
                        <i class="material-icons left">cloud_download</i>
                        Importar todo el período
                    </button>
                </div>
            </div>
        </form>
    </div>
    @else
    <div class="card-panel green lighten-5" style="border-left: 4px solid #2e7d32;">
        <span class="green-text text-darken-3">
            <i class="material-icons tiny">check_circle</i>
            Todos los documentos de {{ $nombreMes }} ya están importados.
        </span>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- BLOQUE 3: DETALLE POR DOCUMENTO (collapsible)             --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    @if(!$documentos->isEmpty())
    <div class="card-panel">
        <div class="row valign-wrapper" style="margin: 0; cursor: pointer;" id="toggle-detalle">
            <div class="col s10">
                <h6 style="margin: 0; font-weight: 600;">
                    <i class="material-icons tiny">list</i>
                    Detalle de documentos
                </h6>
            </div>
            <div class="col s2 right-align">
                <i class="material-icons" id="icon-toggle">expand_more</i>
            </div>
        </div>

        <div id="tabla-detalle" style="display: none; margin-top: 16px;">
            <table class="responsive-table striped" style="font-size: 12px;">
                <thead>
                    <tr style="background: #eceff1;">
                        <th>Tipo</th>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>RUT</th>
                        <th class="right-align">Neto</th>
                        <th class="right-align">IVA</th>
                        <th class="right-align">Total</th>
                        <th class="center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documentos as $doc)
                    <tr class="{{ $doc['ya_importado'] ? 'grey lighten-4' : '' }}">
                        <td><span class="chip" style="font-size: 11px; height: 22px; line-height: 22px;">{{ $doc['tipo_nombre'] }}</span></td>
                        <td>{{ $doc['folio'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($doc['fecha_documento'])->format('d-m-Y') }}</td>
                        <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $doc['razon_social'] ?? '—' }}</td>
                        <td><small class="grey-text">{{ $doc['rut_emisor'] }}</small></td>
                        <td class="right-align">
                            @if($doc['monto_neto']) ${{ number_format($doc['monto_neto'], 0, ',', '.') }}
                            @else <span class="grey-text">—</span> @endif
                        </td>
                        <td class="right-align">
                            @if($doc['monto_iva']) ${{ number_format($doc['monto_iva'], 0, ',', '.') }}
                            @else <span class="grey-text">—</span> @endif
                        </td>
                        <td class="right-align"><strong>${{ number_format($doc['monto_total'], 0, ',', '.') }}</strong></td>
                        <td class="center">
                            @if($doc['ya_importado'])
                                <span class="green-text" style="font-size: 12px;"><i class="material-icons tiny">check_circle</i> Importado</span>
                            @else
                                <span class="orange-text" style="font-size: 12px;"><i class="material-icons tiny">schedule</i> Pendiente</span>
                            @endif
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

@section('foot')
<script>
$(function () {

    // Cargar subcategorías al cambiar categoría (form importar todo)
    $('#cat-todo').on('change', function () {
        var catId = $(this).val();
        var $sub  = $('#subcat-todo');
        $sub.html('<option value="" disabled selected>Cargando...</option>');
        $('#btn-importar-todo').prop('disabled', true);

        $.get('/subcategorias/' + catId, function (data) {
            $sub.html('<option value="" disabled selected>-- Selecciona subcategoría --</option>');
            data.forEach(function (item) {
                $sub.append('<option value="' + item.id + '">' + item.nombre + '</option>');
            });
        }).fail(function () {
            $sub.html('<option value="" disabled selected>Error al cargar</option>');
        });
    });

    $('#subcat-todo').on('change', function () {
        $('#btn-importar-todo').prop('disabled', !$(this).val());
    });

    // Confirmar antes de importar todo
    $('#form-importar-todo').on('submit', function (e) {
        var pendientes = {{ $documentos->where('ya_importado', false)->count() }};
        var subcat     = $('#subcat-todo').val();
        if (!subcat) { e.preventDefault(); return; }

        if (!confirm('¿Importar ' + pendientes + ' documento(s) pendientes del período?\n\nLos duplicados serán omitidos automáticamente.')) {
            e.preventDefault();
        }
    });

    // Toggle detalle
    $('#toggle-detalle').on('click', function () {
        var $tabla = $('#tabla-detalle');
        var $icon  = $('#icon-toggle');
        if ($tabla.is(':visible')) {
            $tabla.slideUp(200);
            $icon.text('expand_more');
        } else {
            $tabla.slideDown(200);
            $icon.text('expand_less');
        }
    });

});
</script>
@endsection
