@extends('themes.backoffice.layouts.admin')

@section('title', 'Factura #' . $foto->id)

@section('content')
<div class="section">
  @php
    $d = $foto->extraccion ?: [];
    $v = $foto->verificacion ?: [];
    $editable = $foto->estado !== 'confirmada';
    $peso = function ($n) { return $n === null || $n === '' ? '—' : '$' . number_format((float) $n, 0, ',', '.'); };
    $num = function ($n) { return $n === null ? '' : rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.'); };
    $veredicto = [
      'coincide'        => ['#e8f5e9', '#1b5e20', 'Coincide con la factura registrada en el SII.'],
      'con_diferencias' => ['#ffebee', '#b71c1c', 'Hay diferencias con el SII o en la propia factura. Revisa abajo.'],
      'no_encontrada'   => ['#fff3e0', '#e65100', 'Aún no aparece en el registro de compras del SII.'],
      'sin_datos'       => ['#fff3e0', '#e65100', 'Falta RUT o folio para buscarla en el SII.'],
    ][$v['estado'] ?? ''] ?? null;
  @endphp
  <p class="caption"><strong>Factura {{ $d['razon_social'] ?? '' }} {{ !empty($d['folio']) ? '· folio ' . $d['folio'] : '' }}</strong>
    <span class="grey-text">· {{ ['pendiente' => 'Por revisar', 'confirmada' => 'Confirmada', 'descartada' => 'Descartada', 'error' => 'Error al leer'][$foto->estado] ?? $foto->estado }}</span></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  @if($foto->estado === 'error')
    <div class="inv-msg err">No se pudo leer la factura: {{ $foto->error }}</div>
    <form method="POST" action="{{ route('backoffice.inventario.facturas.releer', $foto) }}">@csrf<button class="btn" type="submit">Volver a leer</button></form>
  @endif

  @if($veredicto && $foto->estado !== 'error')
    <div class="inv-msg" style="background:{{ $veredicto[0] }};color:{{ $veredicto[1] }}">
      <strong>{{ $veredicto[2] }}</strong>
      @if(!empty($v['sii'])) SII: neto {{ $peso($v['sii']['neto']) }} · IVA {{ $peso($v['sii']['iva']) }} · total {{ $peso($v['sii']['total']) }}.@endif
      @foreach($v['diferencias'] ?? [] as $dif)<div>• {{ $dif }}</div>@endforeach
    </div>
  @endif
  @if(!empty($d['observaciones']))<div class="inv-msg" style="background:#eceff1;color:#37474f">Nota de la lectura: {{ $d['observaciones'] }}</div>@endif

  <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px">
    @foreach($foto->archivos as $n => $ruta)
      <a href="{{ route('backoffice.inventario.facturas.archivo', [$foto, $n]) }}" target="_blank" rel="noopener">
        @if(\Illuminate\Support\Str::endsWith(strtolower($ruta), '.pdf'))<span class="btn-flat">PDF {{ $n + 1 }}</span>
        @else<img src="{{ route('backoffice.inventario.facturas.archivo', [$foto, $n]) }}" style="height:140px;border-radius:4px;border:1px solid #ddd" alt="Foto {{ $n + 1 }} de la factura">@endif
      </a>
    @endforeach
  </div>

  @if($foto->estado !== 'error')
  <form method="POST" action="{{ route('backoffice.inventario.facturas.actualizar', $foto) }}">
    @csrf
    <div class="card-panel">
      <div class="row" style="margin-bottom:0">
        <div class="col s12 m3"><label>RUT emisor</label><input type="text" name="cab[rut_emisor]" value="{{ $d['rut_emisor'] ?? '' }}" {{ $editable ? '' : 'readonly' }}></div>
        <div class="col s12 m5"><label>Razón social</label><input type="text" name="cab[razon_social]" value="{{ $d['razon_social'] ?? '' }}" {{ $editable ? '' : 'readonly' }}></div>
        <div class="col s6 m2"><label>Folio</label><input type="text" name="cab[folio]" value="{{ $d['folio'] ?? '' }}" {{ $editable ? '' : 'readonly' }}></div>
        <div class="col s6 m2"><label>Fecha</label><input type="date" name="cab[fecha_emision]" value="{{ $d['fecha_emision'] ?? '' }}" {{ $editable ? '' : 'readonly' }}></div>
      </div>
      <div class="row" style="margin-bottom:0">
        <div class="col s6 m3"><label>Neto ($)</label><input type="number" step="1" name="cab[neto]" value="{{ $d['neto'] ?? '' }}" {{ $editable ? '' : 'readonly' }}></div>
        <div class="col s6 m3"><label>Exento ($)</label><input type="number" step="1" name="cab[exento]" value="{{ $d['exento'] ?? '' }}" {{ $editable ? '' : 'readonly' }}></div>
        <div class="col s6 m3"><label>IVA ($)</label><input type="number" step="1" name="cab[iva]" value="{{ $d['iva'] ?? '' }}" {{ $editable ? '' : 'readonly' }}></div>
        <div class="col s6 m3"><label>Total ($)</label><input type="number" step="1" name="cab[total]" value="{{ $d['total'] ?? '' }}" {{ $editable ? '' : 'readonly' }}></div>
      </div>
      <input type="hidden" name="cab[tipo_documento]" value="{{ $d['tipo_documento'] ?? 'factura' }}">
    </div>

    <div class="card-panel" style="overflow-x:auto">
      <p style="font-weight:600;margin-top:0">Productos ({{ count($d['lineas'] ?? []) }}) · suma {{ $peso($v['suma_lineas'] ?? null) }}</p>
      <table style="min-width:820px">
        <thead><tr><th>Código</th><th>Descripción</th><th>Cantidad</th><th>Unidad</th><th>Precio unit.</th><th>Monto</th></tr></thead>
        <tbody id="lineas">
        @foreach(($d['lineas'] ?? []) as $i => $l)
          <tr>
            <td><input type="text" name="lin[{{ $i }}][codigo]" value="{{ $l['codigo'] ?? '' }}" style="margin:0;width:90px" {{ $editable ? '' : 'readonly' }}></td>
            <td><input type="text" name="lin[{{ $i }}][descripcion]" value="{{ $l['descripcion'] ?? '' }}" style="margin:0;min-width:260px" {{ $editable ? '' : 'readonly' }}></td>
            <td><input type="number" step="any" name="lin[{{ $i }}][cantidad]" value="{{ $num($l['cantidad'] ?? null) }}" style="margin:0;width:80px" {{ $editable ? '' : 'readonly' }}></td>
            <td><input type="text" name="lin[{{ $i }}][unidad]" value="{{ $l['unidad'] ?? '' }}" style="margin:0;width:60px" {{ $editable ? '' : 'readonly' }}></td>
            <td style="white-space:nowrap">$ <input type="number" step="1" name="lin[{{ $i }}][precio_unitario]" value="{{ isset($l['precio_unitario']) ? (int) round($l['precio_unitario']) : '' }}" style="margin:0;width:100px" {{ $editable ? '' : 'readonly' }}></td>
            <td style="white-space:nowrap">$ <input type="number" step="1" name="lin[{{ $i }}][monto]" value="{{ isset($l['monto']) ? (int) round($l['monto']) : '' }}" style="margin:0;width:110px" {{ $editable ? '' : 'readonly' }}></td>
          </tr>
        @endforeach
        </tbody>
      </table>
      @if($editable)<a href="#!" id="agregar" class="btn-flat"><i class="material-icons left">add</i>Agregar línea</a>@endif
    </div>

    @if($editable)
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn-flat waves-effect" type="submit"><i class="material-icons left">fact_check</i>Guardar cambios y verificar</button>
      </div>
    @endif
  </form>

  @if($editable)
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
      <form method="POST" action="{{ route('backoffice.inventario.facturas.confirmar', $foto) }}">@csrf
        <button class="btn waves-effect" type="submit"><i class="material-icons left">check</i>Confirmar factura</button></form>
      <form method="POST" action="{{ route('backoffice.inventario.facturas.descartar', $foto) }}">@csrf
        <button class="btn-flat red-text" type="submit">Descartar</button></form>
    </div>
    <p class="grey-text" style="font-size:.85rem">Si corriges algo, primero "Guardar cambios y verificar"; después confirma.</p>
  @endif
  @endif

  @if($foto->pedido)
    <div class="card-panel" style="overflow-x:auto">
      <p style="font-weight:600;margin-top:0">Pedido #{{ $foto->pedido->id }} · {{ $foto->pedido->nombreProveedor() }} · <a href="{{ route('backoffice.inventario.compras.show', $foto->pedido) }}">ver pedido</a></p>
      <table style="min-width:520px">
        <thead><tr><th>Insumo</th><th class="inv-num">Pedido</th><th class="inv-num">Comprado</th><th class="inv-num">Recibido</th></tr></thead>
        <tbody>
        @foreach($foto->pedido->items as $it)
          @php $um = optional(optional($it->insumo)->unidadMedida)->abreviatura; @endphp
          <tr><td>{{ optional($it->insumo)->nombre }}</td>
            <td class="inv-num">{{ $num($it->cantidad_pedida) }} {{ $um }}</td>
            <td class="inv-num">{{ $it->cantidad_comprada !== null ? $num($it->cantidad_comprada) . ' ' . $um : '—' }}</td>
            <td class="inv-num">{{ $it->cantidad_recibida !== null ? $num($it->cantidad_recibida) . ' ' . $um : '—' }}</td></tr>
        @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection

@section('foot')
<script>
  (function () {
    var a = document.getElementById('agregar'); if (!a) return;
    a.addEventListener('click', function (e) {
      e.preventDefault();
      var tb = document.getElementById('lineas'), i = tb.rows.length + 100, tr = document.createElement('tr');
      tr.innerHTML = '<td><input type="text" name="lin[' + i + '][codigo]" style="margin:0;width:90px"></td>' +
        '<td><input type="text" name="lin[' + i + '][descripcion]" style="margin:0;min-width:260px"></td>' +
        '<td><input type="number" step="any" name="lin[' + i + '][cantidad]" style="margin:0;width:80px"></td>' +
        '<td><input type="text" name="lin[' + i + '][unidad]" style="margin:0;width:60px"></td>' +
        '<td style="white-space:nowrap">$ <input type="number" step="1" name="lin[' + i + '][precio_unitario]" style="margin:0;width:100px"></td>' +
        '<td style="white-space:nowrap">$ <input type="number" step="1" name="lin[' + i + '][monto]" style="margin:0;width:110px"></td>';
      tb.appendChild(tr);
    });
  })();
</script>
@endsection
