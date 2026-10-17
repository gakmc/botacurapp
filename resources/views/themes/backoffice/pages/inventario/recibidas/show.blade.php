@extends('themes.backoffice.layouts.admin')

@section('title', 'Factura ' . $cab->folio)

@section('content')
<div class="section">
  @php
    $peso = function ($n) { return '$' . number_format((float) $n, 0, ',', '.'); };
    $num = function ($n) { return $n === null ? '' : rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.'); };
    $netoTotal = $lineas->sum('monto');
  @endphp
  <p class="caption"><strong>{{ $cab->razon_social }} · folio {{ $cab->folio }} · {{ \Carbon\Carbon::parse($cab->fecha)->format('d/m/Y') }}</strong>
    <span class="grey-text">· {{ $cab->origen === 'foto' ? 'leída de foto' : 'XML del SII' }} · neto {{ $peso($netoTotal) }}</span></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  <p style="max-width:75ch;color:#546e7a">
    Marca los productos que van al inventario y elige a qué insumo y sector corresponden. Lo que no es inventario (ferretería, servicios, etc.) déjalo sin marcar.
    La <strong>conversión</strong> dice cuántas unidades del insumo trae cada unidad de la factura (ej. "PAPA MALLA 2KGS" = 2 kg). Entra al stock con la cantidad comprada y el costo con IVA incluido; la próxima factura de este proveedor vendrá marcada igual.
  </p>

  @if($pedidoRecibido)
    <div class="inv-msg err">
      Esta factura está ligada al pedido #{{ $pedidoRecibido->id }}, que ya sumó al inventario en su recepción. Si vuelves a agregar los mismos productos aquí, el stock quedará <strong>duplicado</strong>.
      Úsalo solo para productos que no estaban en el pedido. <a href="{{ route('backoffice.inventario.compras.show', $pedidoRecibido->id) }}">Ver pedido</a>
    </div>
  @endif

  <form method="POST" action="{{ route('backoffice.inventario.recibidas.agregar', [$cab->rut_emisor, $cab->dte, $cab->folio]) }}" id="form-agregar">
    @csrf
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:8px">
      <label><input type="checkbox" class="filled-in" id="todos"><span>Marcar todos los pendientes</span></label>
      <span class="grey-text">·</span>
      <span>Sector para los marcados:</span>
      <select class="browser-default" id="sector-masivo" style="width:auto">
        <option value="">—</option>
        @foreach($sectores as $s)<option value="{{ $s->id }}">{{ $s->nombre }}</option>@endforeach
      </select>
    </div>

    <div class="card-panel" style="overflow-x:auto;padding:10px">
      <table style="min-width:1150px">
        <thead><tr>
          <th></th><th>Producto en la factura</th><th class="inv-num">Neto</th><th class="inv-num">Con IVA</th>
          <th>Insumo del inventario</th><th>Sector</th><th>Conversión</th><th class="inv-num">Entra al stock</th><th class="inv-num">Costo c/IVA por unidad</th>
        </tr></thead>
        <tbody>
        @foreach($lineas as $l)
          @php
            $bloqueada = $l->inventario === 'agregado' || (float) $l->monto <= 0 || (float) $l->cantidad <= 0;
            $sugerido = $l->sug_insumo ? $insumos->firstWhere('id', $l->sug_insumo) : null;
            $sectorDef = $sugerido ? $sugerido->id_sector : ($sectorProv ?: 1);
          @endphp
          <tr class="js-linea" data-cant="{{ (float) $l->cantidad }}" data-monto="{{ (float) $l->monto }}" data-iva="{{ $l->iva }}" style="{{ $bloqueada ? 'opacity:.55' : '' }}">
            <td>
              @if($l->inventario === 'agregado')
                <i class="material-icons green-text" title="Ya está en el inventario">check_circle</i>
              @elseif(! $bloqueada)
                <label><input type="checkbox" class="filled-in js-sel" name="sel[{{ $l->id }}]" value="1" {{ $l->recordado && $l->inventario !== 'omitido' ? 'checked' : '' }}><span></span></label>
              @endif
            </td>
            <td style="min-width:240px">
              <div>{{ $l->nombre }}</div>
              <div class="grey-text" style="font-size:.82rem">{{ $l->codigo ? 'cód. ' . $l->codigo . ' · ' : '' }}{{ $num($l->cantidad) }} {{ $l->unidad }} × {{ $peso($l->precio) }}
                @if($l->inventario === 'agregado') · <span class="green-text">en inventario: {{ $num($l->cantidad_inventario) }}</span>@endif
                @if($l->inventario === 'omitido') · <span class="orange-text">marcado como no inventario</span>@endif
                @if($l->recordado && $l->inventario === null) · <span class="teal-text">asociación recordada</span>@endif
              </div>
            </td>
            <td class="inv-num">{{ $peso($l->monto) }}</td>
            <td class="inv-num">{{ $peso($l->monto * $l->iva) }}</td>
            @if($bloqueada)
              <td colspan="5" class="grey-text" style="font-size:.85rem">{{ (float) $l->monto <= 0 ? 'Nota de crédito / sin monto' : '' }}</td>
            @else
              <td style="min-width:220px">
                <select name="l[{{ $l->id }}][id_insumo]" class="browser-default js-insumo">
                  <option value="">Elegir insumo…</option>
                  <option value="nuevo">+ Crear insumo nuevo</option>
                  @foreach($insumos as $i)<option value="{{ $i->id }}" data-um="{{ optional($i->unidadMedida)->abreviatura }}" data-sector="{{ $i->id_sector }}" {{ (int) $l->sug_insumo === $i->id ? 'selected' : '' }}>{{ $i->nombre }} ({{ optional($i->unidadMedida)->abreviatura }})</option>@endforeach
                </select>
                <div class="js-nuevo" style="display:none;margin-top:4px">
                  <input type="text" name="l[{{ $l->id }}][nuevo_nombre]" placeholder="Nombre del insumo" value="{{ mb_convert_case(mb_strtolower($l->nombre), MB_CASE_TITLE) }}" style="margin:0">
                  <select name="l[{{ $l->id }}][nuevo_unidad]" class="browser-default js-nuevo-um" style="margin-top:4px">
                    @foreach($unidades as $u)<option value="{{ $u->id }}" data-um="{{ $u->abreviatura }}" {{ $u->abreviatura === (in_array(strtoupper($l->unidad), ['KG', 'KGV']) ? 'kg' : 'ud') ? 'selected' : '' }}>{{ $u->nombre }}</option>@endforeach
                  </select>
                </div>
              </td>
              <td><select name="l[{{ $l->id }}][id_sector]" class="browser-default js-sector">
                @foreach($sectores as $s)<option value="{{ $s->id }}" {{ (int) $sectorDef === $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>@endforeach
              </select></td>
              <td style="white-space:nowrap">× <input type="number" step="any" min="0" name="l[{{ $l->id }}][factor]" value="{{ $num($l->sug_factor) }}" class="js-factor" style="width:80px;margin:0"></td>
              <td class="inv-num js-entra"></td>
              <td class="inv-num js-costo"></td>
            @endif
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
      <button class="btn waves-effect" type="submit"><i class="material-icons left">move_to_inbox</i>Pasar seleccionados al inventario</button>
      <label><input type="checkbox" class="filled-in" name="omitir_resto" value="1"><span>Marcar los no seleccionados como "no inventario"</span></label>
      <a class="btn-flat" href="{{ route('backoffice.inventario.recibidas.index') }}">Volver</a>
    </div>
  </form>
</div>
@endsection

@section('foot')
<script>
(function () {
  var fmt = function (n, d) { return Number(n).toLocaleString('es-CL', { maximumFractionDigits: d }); };
  function recalcular(tr) {
    var sel = tr.querySelector('.js-insumo'); if (!sel) return;
    var nuevo = sel.value === 'nuevo';
    tr.querySelector('.js-nuevo').style.display = nuevo ? '' : 'none';
    var um = nuevo ? tr.querySelector('.js-nuevo-um').selectedOptions[0].dataset.um : (sel.selectedOptions[0].dataset.um || '');
    var f = parseFloat(tr.querySelector('.js-factor').value) || 0;
    var cant = parseFloat(tr.dataset.cant) * f;
    var conIva = parseFloat(tr.dataset.monto) * parseFloat(tr.dataset.iva);
    tr.querySelector('.js-entra').textContent = sel.value ? fmt(cant, 3) + ' ' + um : '';
    tr.querySelector('.js-costo').textContent = sel.value && cant > 0 ? '$' + fmt(conIva / cant, 0) + ' / ' + um : '';
  }
  document.querySelectorAll('.js-linea').forEach(function (tr) {
    ['.js-insumo', '.js-factor', '.js-nuevo-um'].forEach(function (s) {
      var el = tr.querySelector(s); if (el) el.addEventListener(s === '.js-factor' ? 'input' : 'change', function () {
        recalcular(tr);
        var cb = tr.querySelector('.js-sel'); if (cb && s === '.js-insumo' && el.value) cb.checked = true;
        if (s === '.js-insumo' && el.selectedOptions[0] && el.selectedOptions[0].dataset.sector) tr.querySelector('.js-sector').value = el.selectedOptions[0].dataset.sector;
      });
    });
    recalcular(tr);
  });
  document.getElementById('todos').addEventListener('change', function (e) {
    document.querySelectorAll('.js-sel').forEach(function (c) { c.checked = e.target.checked; });
  });
  document.getElementById('sector-masivo').addEventListener('change', function (e) {
    if (!e.target.value) return;
    document.querySelectorAll('.js-linea').forEach(function (tr) {
      var cb = tr.querySelector('.js-sel'); if (cb && cb.checked) tr.querySelector('.js-sector').value = e.target.value;
    });
  });
  document.getElementById('form-agregar').addEventListener('submit', function (e) {
    var faltan = Array.prototype.filter.call(document.querySelectorAll('.js-linea'), function (tr) {
      var cb = tr.querySelector('.js-sel'); return cb && cb.checked && !tr.querySelector('.js-insumo').value;
    });
    if (faltan.length) { e.preventDefault(); alert('Hay ' + faltan.length + ' producto(s) marcados sin insumo elegido.'); }
  });
})();
</script>
@endsection
