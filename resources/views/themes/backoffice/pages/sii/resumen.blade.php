@extends('themes.backoffice.layouts.admin')

@section('title', 'Resumen Mensual SII')

@section('breadcrumbs')
<li><a href="{{ route('backoffice.egreso.index') }}">Egresos</a></li>
<li><a href="{{ route('backoffice.sii.index') }}">SII</a></li>
<li>Resumen Mensual</li>
@endsection

@section('content')
<div class="section">
    <div class="card-panel">

        <div class="row valign-wrapper" style="margin-bottom:0;">
            <div class="col s12 m8">
                <h5 style="margin:0;">Importaciones SII — {{ $anio }}</h5>
                <p class="grey-text" style="margin:4px 0 0; font-size:13px;">
                    Facturas de compra importadas desde el RCV del SII por mes.
                </p>
            </div>
            <div class="col s12 m4 right-align" style="display:flex; align-items:center; justify-content:flex-end; gap:10px;">
                <button id="btn-importar-todos" type="button"
                    class="btn waves-effect waves-light"
                    style="background:#039B7B; font-size:12px; height:36px; line-height:36px; padding:0 14px;">
                    <i class="material-icons left tiny">cloud_download</i> Importar todos
                </button>
                <form method="GET" action="{{ route('backoffice.sii.resumen') }}" style="display:inline-flex; align-items:center;">
                    <select name="anio" class="browser-default" onchange="this.form.submit()"
                        style="border:1px solid #bdbdbd; border-radius:4px; padding:6px 10px; background:#fff; width:100px;">
                        @foreach($anios as $a)
                            <option value="{{ $a }}" {{ $a == $anio ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        <div class="divider" style="margin: 16px 0 20px;"></div>

        {{-- Alerta global --}}
        <div id="alerta-global" style="display:none; margin-bottom:12px;"></div>

        <table class="striped" id="tabla-resumen" style="font-size:13px; table-layout:fixed; width:100%;">
            <colgroup>
                <col style="width:22%;">
                <col style="width:8%;">
                <col style="width:16%;">
                <col style="width:14%;">
                <col style="width:16%;">
                <col style="width:24%;">
            </colgroup>
            <thead>
                <tr style="background:#eceff1;">
                    <th>Período</th>
                    <th class="center">Docs</th>
                    <th class="right-align">Neto</th>
                    <th class="right-align">IVA</th>
                    <th class="right-align">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php $totalAnio = 0; $totalNeto = 0; $totalIva = 0; $totalDocs = 0; @endphp
                @foreach($meses as $fila)
                @php
                    $esFuturo = ($anio == now()->year && $fila['mes'] > now()->month)
                                || $anio > now()->year;
                    if ($fila['importado']) {
                        $totalAnio += $fila['total'];
                        $totalNeto += $fila['neto'];
                        $totalIva  += $fila['iva'];
                        $totalDocs += $fila['documentos'];
                    }
                @endphp
                <tr id="fila-mes-{{ $fila['mes'] }}"
                    data-mes="{{ $fila['mes'] }}"
                    data-anio="{{ $anio }}"
                    data-importado="{{ $fila['importado'] ? '1' : '0' }}">

                    <td style="font-weight:500;">{{ $fila['nombre'] }} {{ $anio }}</td>

                    <td class="center grey-text celda-docs">
                        {{ $fila['importado'] ? $fila['documentos'] : '—' }}
                    </td>

                    <td class="right-align celda-neto">
                        @if($fila['importado'])
                            <span class="grey-text">${{ number_format($fila['neto'], 0, ',', '.') }}</span>
                        @else
                            <span class="grey-text">—</span>
                        @endif
                    </td>

                    <td class="right-align celda-iva">
                        @if($fila['importado'])
                            <span class="grey-text">${{ number_format($fila['iva'], 0, ',', '.') }}</span>
                        @else
                            <span class="grey-text">—</span>
                        @endif
                    </td>

                    <td class="right-align celda-total">
                        @if($fila['importado'])
                            <strong style="color:#039B7B;">${{ number_format($fila['total'], 0, ',', '.') }}</strong>
                        @elseif(!$esFuturo)
                            <span style="color:#e53935; font-size:11px; font-weight:600; letter-spacing:.4px;">SIN IMPORTAR</span>
                        @else
                            <span class="grey-text">—</span>
                        @endif
                    </td>

                    <td class="celda-accion">
                        @if(!$esFuturo)
                            @if($fila['importado'])
                            <a href="{{ route('backoffice.sii.detalleMes', ['mes' => $fila['mes'], 'anio' => $anio]) }}"
                               class="btn-flat btn-small waves-effect" style="color:#039B7B; font-size:12px;">
                                Ver detalle <i class="material-icons tiny right">arrow_forward</i>
                            </a>
                            @else
                            <button type="button"
                                class="btn-importar-mes btn-flat btn-small waves-effect"
                                data-mes="{{ $fila['mes'] }}"
                                data-anio="{{ $anio }}"
                                style="color:#e53935; font-size:12px;">
                                <i class="material-icons tiny left">cloud_download</i> Importar
                            </button>
                            @endif
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f0faf7; font-weight:700; font-size:13px;">
                    <td>TOTAL {{ $anio }}</td>
                    <td class="center" id="pie-docs">{{ $totalDocs }}</td>
                    <td class="right-align" id="pie-neto">${{ number_format($totalNeto, 0, ',', '.') }}</td>
                    <td class="right-align" id="pie-iva">${{ number_format($totalIva, 0, ',', '.') }}</td>
                    <td class="right-align" style="color:#039B7B;" id="pie-total">${{ number_format($totalAnio, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

    </div>
</div>

{{-- Overlay de carga --}}
<div id="overlay-importando" style="
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,.55); align-items:center; justify-content:center; flex-direction:column;">
    <div style="background:#fff; border-radius:8px; padding:40px 48px; text-align:center; max-width:380px;">
        <div class="preloader-wrapper big active" style="margin-bottom:24px;">
            <div class="spinner-layer spinner-green-only">
                <div class="circle-clipper left"><div class="circle"></div></div>
                <div class="gap-patch"><div class="circle"></div></div>
                <div class="circle-clipper right"><div class="circle"></div></div>
            </div>
        </div>
        <h6 style="margin:0 0 8px; font-weight:700; color:#333;" id="overlay-titulo">Importando...</h6>
        <p class="grey-text" style="font-size:13px; margin:0;">
            Consultando el SII en tiempo real.<br>
            Esto puede tardar hasta <strong>9 minutos</strong>.<br>
            Por favor no cierres esta ventana.
        </p>
    </div>
</div>
@endsection

@section('foot')
<script>
$(function () {

    var urlImportar   = '{{ route("backoffice.sii.importarDirecto") }}';
    var urlDetalleMes = '{{ route("backoffice.sii.detalleMes") }}';
    var token         = '{{ csrf_token() }}';
    var pieDocs  = {{ $totalDocs }};
    var pieNeto  = {{ $totalNeto }};
    var pieIva   = {{ $totalIva }};
    var pieTotal = {{ $totalAnio }};

    function fmt(n) {
        return '$' + parseInt(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function mostrarOverlay(nombreMes) {
        $('#overlay-titulo').text('Importando ' + nombreMes + '...');
        $('#overlay-importando').css('display', 'flex');
    }

    function ocultarOverlay() {
        $('#overlay-importando').hide();
    }

    function mostrarAlerta(tipo, msg) {
        var bg  = tipo === 'ok' ? '#e8f5e9' : '#ffebee';
        var col = tipo === 'ok' ? '#2e7d32' : '#c62828';
        var ico = tipo === 'ok' ? 'check_circle' : 'error';
        $('#alerta-global')
            .html('<div style="padding:12px 16px; border-radius:4px; background:' + bg + '; color:' + col + '; font-size:13px;">'
                + '<i class="material-icons tiny" style="vertical-align:middle; margin-right:6px;">' + ico + '</i>' + msg + '</div>')
            .show();
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    // ── IMPORTAR TODOS (secuencial) ──────────────────────────────────────────
    $('#btn-importar-todos').on('click', function () {
        var pendientes = [];
        $('tr[data-importado="0"]').each(function () {
            var mes  = $(this).data('mes');
            var anio = $(this).data('anio');
            if (mes && anio) pendientes.push({ mes: mes, anio: anio });
        });

        if (pendientes.length === 0) {
            mostrarAlerta('ok', 'Todos los meses ya están importados.');
            return;
        }

        $('#btn-importar-todos').prop('disabled', true).text('Importando...');

        function importarSiguiente(idx) {
            if (idx >= pendientes.length) {
                $('#btn-importar-todos').prop('disabled', false)
                    .html('<i class="material-icons left tiny">cloud_download</i> Importar todos');
                mostrarAlerta('ok', 'Importación completa: ' + pendientes.length + ' mes(es) procesados.');
                return;
            }

            var item      = pendientes[idx];
            var $fila     = $('#fila-mes-' + item.mes);
            var nombreMes = $.trim($fila.find('td:first').text());

            mostrarOverlay(nombreMes + ' (' + (idx + 1) + '/' + pendientes.length + ')');

            $.ajax({
                url    : urlImportar,
                method : 'POST',
                timeout: 0,
                data   : { _token: token, anio: item.anio, mes: item.mes },
                success: function (resp) {
                    if (resp.ok) {
                        var urlDetalle = urlDetalleMes + '?mes=' + item.mes + '&anio=' + item.anio;
                        $fila.find('.celda-docs').text(resp.docs || (resp.importados + resp.omitidos));
                        $fila.find('.celda-neto').html('<span class="grey-text">' + fmt(resp.neto || 0) + '</span>');
                        $fila.find('.celda-iva').html('<span class="grey-text">' + fmt(resp.iva || 0) + '</span>');
                        $fila.find('.celda-total').html('<strong style="color:#039B7B;">' + fmt(resp.total) + '</strong>');
                        $fila.find('.celda-accion').html(
                            '<a href="' + urlDetalle + '" class="btn-flat btn-small waves-effect" style="color:#039B7B; font-size:12px;">'
                            + 'Ver detalle <i class="material-icons tiny right">arrow_forward</i></a>'
                        );
                        $fila.attr('data-importado', '1');
                        pieDocs  += resp.importados || 0;
                        pieNeto  += resp.neto  || 0;
                        pieIva   += resp.iva   || 0;
                        pieTotal += resp.total || 0;
                        $('#pie-docs').text(pieDocs);
                        $('#pie-neto').text(fmt(pieNeto));
                        $('#pie-iva').text(fmt(pieIva));
                        $('#pie-total').text(fmt(pieTotal));
                    }
                    importarSiguiente(idx + 1);
                },
                error: function () {
                    ocultarOverlay();
                    mostrarAlerta('error', 'Error al importar ' + nombreMes + '. Los meses restantes no se procesaron.');
                    $('#btn-importar-todos').prop('disabled', false)
                        .html('<i class="material-icons left tiny">cloud_download</i> Importar todos');
                }
            });
        }

        importarSiguiente(0);
    });

    $(document).on('click', '.btn-importar-mes', function () {
        var $btn      = $(this);
        var mes       = $btn.data('mes');
        var anio      = $btn.data('anio');
        var $fila     = $('#fila-mes-' + mes);
        var nombreMes = $.trim($fila.find('td:first').text());

        mostrarOverlay(nombreMes);
        $btn.prop('disabled', true);

        $.ajax({
            url    : urlImportar,
            method : 'POST',
            timeout: 0,
            data   : { _token: token, anio: anio, mes: mes },
            success: function (resp) {
                ocultarOverlay();

                if (!resp.ok) {
                    mostrarAlerta('error', 'Error: ' + (resp.error || 'desconocido'));
                    $btn.prop('disabled', false);
                    return;
                }

                var urlDetalle = urlDetalleMes + '?mes=' + mes + '&anio=' + anio;
                var totalFmt   = fmt(resp.total);
                var netoFmt    = fmt(resp.neto   || 0);
                var ivaFmt     = fmt(resp.iva    || 0);
                // Usar docs reales desde DB; si no viene, sumar importados + omitidos
                var docsN      = resp.docs || (resp.importados + resp.omitidos);

                $fila.find('.celda-docs').text(docsN);
                $fila.find('.celda-neto').html('<span class="grey-text">' + netoFmt + '</span>');
                $fila.find('.celda-iva').html('<span class="grey-text">' + ivaFmt + '</span>');
                $fila.find('.celda-total').html('<strong style="color:#039B7B;">' + totalFmt + '</strong>');
                $fila.find('.celda-accion').html(
                    '<a href="' + urlDetalle + '" class="btn-flat btn-small waves-effect" style="color:#039B7B; font-size:12px;">'
                    + 'Ver detalle <i class="material-icons tiny right">arrow_forward</i></a>'
                );

                pieDocs  += resp.importados;
                pieNeto  += (resp.neto  || 0);
                pieIva   += (resp.iva   || 0);
                pieTotal += resp.total;
                $('#pie-docs').text(pieDocs);
                $('#pie-neto').text(fmt(pieNeto));
                $('#pie-iva').text(fmt(pieIva));
                $('#pie-total').text(fmt(pieTotal));

                var msg = '<strong>' + resp.importados + ' factura(s)</strong> importadas de ' + nombreMes;
                if (resp.omitidos > 0) msg += ' (' + resp.omitidos + ' ya existían)';
                msg += ' — Total: ' + totalFmt;
                mostrarAlerta('ok', msg);
            },
            error: function (xhr) {
                ocultarOverlay();
                var msg = 'Error al importar. Intenta nuevamente.';
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.error)   msg = data.error;
                    if (data.message) msg = data.message;
                } catch(e) {}
                mostrarAlerta('error', msg);
                $btn.prop('disabled', false);
            }
        });
    });

});
</script>
@endsection
