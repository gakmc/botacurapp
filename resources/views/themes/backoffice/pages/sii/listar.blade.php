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
    <div class="card-panel">

        <div class="row valign-wrapper" style="margin-bottom: 0;">
            <div class="col s12 m8">
                <h5 style="margin: 0;">Facturas de Compra – {{ $nombreMes }}</h5>
                <p class="grey-text" style="margin: 4px 0 0;">
                    {{ $documentos->count() }} documento(s) encontrado(s) en el RCV
                    · {{ $documentos->where('ya_importado', false)->count() }} pendiente(s) de importar
                </p>
            </div>
            <div class="col s12 m4 right-align">
                <button id="btn-seleccionar-todos" class="btn-flat waves-effect">
                    <i class="material-icons left">select_all</i> Seleccionar pendientes
                </button>
            </div>
        </div>

        <div class="divider" style="margin: 16px 0 24px;"></div>

        @if($documentos->isEmpty())
        <p class="center grey-text">No se encontraron documentos para este período.</p>
        @else

        <form action="{{ route('backoffice.sii.importar') }}" method="POST" id="form-importar">
            @csrf

            {{-- Selects globales de categoría (se aplican a todos los seleccionados) --}}
            <div class="row" style="background: #f5f5f5; padding: 16px; border-radius: 4px; margin-bottom: 20px;">
                <p class="col s12" style="margin: 0 0 10px;"><strong>Asignar a todos los seleccionados:</strong></p>

                <div class="input-field col s12 m4">
                    <select id="categoria_global">
                        <option value="" disabled selected>-- Categoría --</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                    <label>Categoría (global)</label>
                </div>

                <div class="input-field col s12 m4">
                    <select id="subcategoria_global">
                        <option value="" disabled selected>-- Subcategoría --</option>
                    </select>
                    <label>Subcategoría (global)</label>
                </div>

                <div class="col s12 m4" style="margin-top: 26px;">
                    <button type="button" id="btn-aplicar-global" class="btn-flat waves-effect">
                        <i class="material-icons left">check</i> Aplicar a seleccionados
                    </button>
                </div>
            </div>

            <table class="responsive-table striped" id="tabla-documentos">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <label>
                                <input type="checkbox" id="chk-all" class="filled-in">
                                <span></span>
                            </label>
                        </th>
                        <th>Tipo</th>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>RUT</th>
                        <th class="right-align">Neto</th>
                        <th class="right-align">IVA</th>
                        <th class="right-align">Total</th>
                        <th>Categoría</th>
                        <th>Subcategoría</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documentos as $i => $doc)
                    <tr class="{{ $doc['ya_importado'] ? 'grey lighten-3' : '' }}">

                        {{-- Checkbox --}}
                        <td>
                            @if($doc['ya_importado'])
                                <i class="material-icons tiny green-text tooltipped"
                                   data-position="right" data-tooltip="Ya importado">check_circle</i>
                            @else
                                <label>
                                    <input type="checkbox" class="filled-in chk-doc" name="chk_{{ $i }}"
                                           data-index="{{ $i }}" value="1">
                                    <span></span>
                                </label>
                            @endif
                        </td>

                        {{-- Datos del DTE --}}
                        <td>
                            <span class="chip" style="font-size: 11px;">{{ $doc['tipo_nombre'] }}</span>
                            {{-- Campos ocultos enviados al importar --}}
                            <input type="hidden" name="documentos[{{ $i }}][folio]"            value="{{ $doc['folio'] }}">
                            <input type="hidden" name="documentos[{{ $i }}][rut_emisor]"       value="{{ $doc['rut_emisor'] }}">
                            <input type="hidden" name="documentos[{{ $i }}][razon_social]"     value="{{ $doc['razon_social'] }}">
                            <input type="hidden" name="documentos[{{ $i }}][fecha_documento]"  value="{{ $doc['fecha_documento'] }}">
                            <input type="hidden" name="documentos[{{ $i }}][monto_neto]"       value="{{ $doc['monto_neto'] }}">
                            <input type="hidden" name="documentos[{{ $i }}][monto_iva]"        value="{{ $doc['monto_iva'] }}">
                            <input type="hidden" name="documentos[{{ $i }}][monto_total]"      value="{{ $doc['monto_total'] }}">
                            <input type="hidden" name="documentos[{{ $i }}][tipo_documento]"   value="{{ $doc['tipo_documento'] }}">
                        </td>
                        <td>{{ $doc['folio'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($doc['fecha_documento'])->format('d-m-Y') }}</td>
                        <td>{{ $doc['razon_social'] ?? '—' }}</td>
                        <td><small>{{ $doc['rut_emisor'] }}</small></td>
                        <td class="right-align">
                            @if($doc['monto_neto'])
                                ${{ number_format($doc['monto_neto'], 0, ',', '.') }}
                            @else
                                <span class="grey-text">—</span>
                            @endif
                        </td>
                        <td class="right-align">
                            @if($doc['monto_iva'])
                                ${{ number_format($doc['monto_iva'], 0, ',', '.') }}
                            @else
                                <span class="grey-text">—</span>
                            @endif
                        </td>
                        <td class="right-align">
                            <strong>${{ number_format($doc['monto_total'], 0, ',', '.') }}</strong>
                        </td>

                        {{-- Selects de categoría por fila --}}
                        <td>
                            @if(!$doc['ya_importado'])
                            <div class="input-field" style="margin: 0; min-width: 160px;">
                                <select class="cat-select" name="documentos[{{ $i }}][categoria_id]"
                                        data-index="{{ $i }}" {{ $doc['ya_importado'] ? 'disabled' : '' }}>
                                    <option value="" disabled selected>-- Categoría --</option>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @else
                                <span class="grey-text">—</span>
                            @endif
                        </td>
                        <td>
                            @if(!$doc['ya_importado'])
                            <div class="input-field" style="margin: 0; min-width: 160px;">
                                <select class="subcat-select" name="documentos[{{ $i }}][subcategoria_id]"
                                        data-index="{{ $i }}" {{ $doc['ya_importado'] ? 'disabled' : '' }}>
                                    <option value="" disabled selected>-- Subcategoría --</option>
                                </select>
                            </div>
                            @else
                                <span class="grey-text">—</span>
                            @endif
                        </td>

                        {{-- Estado --}}
                        <td>
                            @if($doc['ya_importado'])
                                <span class="chip green lighten-4">
                                    <span class="green-text text-darken-2">Importado</span>
                                </span>
                            @else
                                <span class="chip orange lighten-4">
                                    <span class="orange-text text-darken-2">Pendiente</span>
                                </span>
                            @endif
                        </td>

                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Barra de acción inferior --}}
            <div class="row" style="margin-top: 24px;">
                <div class="col s12 right-align">
                    <span id="resumen-seleccion" class="grey-text" style="margin-right: 16px;">
                        0 seleccionados · $0
                    </span>
                    <button type="submit" id="btn-importar" class="btn waves-effect" style="background-color: #039B7B;" disabled>
                        <i class="material-icons left">cloud_download</i>
                        Importar seleccionados
                    </button>
                </div>
            </div>

        </form>
        @endif

    </div>
</div>
@endsection

@section('foot')
<script>
$(function () {

    $('select').material_select();

    // Cargar subcategorías al cambiar categoría (por fila)
    $(document).on('change', '.cat-select', function () {
        const idx     = $(this).data('index');
        const catId   = $(this).val();
        const $subcat = $(`.subcat-select[data-index="${idx}"]`);

        cargarSubcategorias(catId, $subcat, null);
    });

    // Categoría global → cargar subcategorías globales
    $('#categoria_global').on('change', function () {
        const catId = $(this).val();
        cargarSubcategorias(catId, $('#subcategoria_global'), null);
    });

    // Aplicar categoría/subcategoría global a todos los checkboxes marcados
    $('#btn-aplicar-global').on('click', function () {
        const catId    = $('#categoria_global').val();
        const subcatId = $('#subcategoria_global').val();
        if (!catId) { Swal.fire('Atención', 'Selecciona una categoría global.', 'warning'); return; }

        $('.chk-doc:checked').each(function () {
            const idx     = $(this).data('index');
            const $catSel = $(`.cat-select[data-index="${idx}"]`);
            const $subSel = $(`.subcat-select[data-index="${idx}"]`);

            $catSel.val(catId).material_select();
            cargarSubcategorias(catId, $subSel, subcatId);
        });
    });

    // Seleccionar todos los pendientes
    $('#btn-seleccionar-todos').on('click', function () {
        $('.chk-doc').prop('checked', true).trigger('change');
        actualizarResumen();
    });

    // Check all
    $('#chk-all').on('change', function () {
        $('.chk-doc').prop('checked', this.checked).trigger('change');
        actualizarResumen();
    });

    $(document).on('change', '.chk-doc', function () {
        actualizarResumen();
    });

    function actualizarResumen () {
        let cant = 0, total = 0;
        $('.chk-doc:checked').each(function () {
            cant++;
            const idx = $(this).data('index');
            // leer el total del hidden input correspondiente
            const t = parseInt($(`input[name="documentos[${idx}][monto_total]"]`).val()) || 0;
            total += t;
        });
        const fmt = '$' + new Intl.NumberFormat('es-CL').format(total);
        $('#resumen-seleccion').text(`${cant} seleccionado(s) · ${fmt}`);
        $('#btn-importar').prop('disabled', cant === 0);
    }

    // Antes de enviar: deshabilitar los que no están chequeados
    // para que no se envíen al backend
    $('#form-importar').on('submit', function (e) {
        // Verificar que todos los seleccionados tengan subcategoría
        let ok = true;
        $('.chk-doc:checked').each(function () {
            const idx     = $(this).data('index');
            const subcatId = $(`.subcat-select[data-index="${idx}"]`).val();
            if (!subcatId) {
                ok = false;
                return false;
            }
        });

        if (!ok) {
            e.preventDefault();
            Swal.fire('Atención', 'Todos los documentos seleccionados deben tener categoría y subcategoría asignadas.', 'warning');
            return;
        }

        // Remover filas no seleccionadas del submit
        $('.chk-doc').each(function () {
            if (!this.checked) {
                const idx = $(this).data('index');
                $(`[name^="documentos[${idx}]"]`).remove();
            }
        });
    });

    function cargarSubcategorias(catId, $select, preselect) {
        $.get('/subcategorias/' + catId, function (data) {
            $select.empty().append('<option value="" disabled selected>-- Subcategoría --</option>');
            data.forEach(function (item) {
                const sel = (preselect && item.id == preselect) ? 'selected' : '';
                $select.append(`<option value="${item.id}" ${sel}>${item.nombre}</option>`);
            });
            $select.material_select();
        });
    }

});
</script>
@endsection
