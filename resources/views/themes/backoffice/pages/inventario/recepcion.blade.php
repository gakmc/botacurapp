@extends('themes.backoffice.layouts.admin')

@section('title', 'Recepción de compras')

@section('content')
<div class="section">
  <p class="caption"><strong>Recepción de compras</strong></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  <p style="max-width:70ch;color:#546e7a">
    Registra lo que llegó a bodega. Si viene con factura, asóciala para que quede ligada a la compra del SII.
    El costo es el <strong>total neto (sin IVA)</strong> de cada línea; la app calcula el costo promedio del insumo.
  </p>

  <form method="POST" action="{{ route('backoffice.inventario.recepcion.store') }}" id="form-recepcion">
    @csrf
    <div class="row">
      <div class="col s12 m3"><label>Fecha de recepción</label>
        <input type="datetime-local" name="fecha" required value="{{ old('fecha', now()->format('Y-m-d\TH:i')) }}"></div>
      <div class="col s12 m6"><label>Factura SII (opcional)</label>
        <select name="egreso_id" class="browser-default" id="egreso">
          <option value="">Sin factura (boleta o compra informal)</option>
          @foreach($egresos as $e)
            <option value="{{ $e->id }}" data-neto="{{ (int) $e->total - (int) $e->iva }}" {{ (string) old('egreso_id', $egresoId) === (string) $e->id ? 'selected' : '' }}>
              {{ \Carbon\Carbon::parse($e->fecha_egreso)->format('d/m') }} · {{ optional($e->proveedor)->nombre ?? $e->descripcion }} · folio {{ $e->numero_documento }} · neto ${{ number_format((int) $e->total - (int) $e->iva, 0, ',', '.') }}
            </option>
          @endforeach
        </select></div>
      <div class="col s12 m3"><label>Observación</label>
        <input type="text" name="observacion" maxlength="255" value="{{ old('observacion') }}"></div>
    </div>

    <div class="card-panel" style="overflow-x:auto">
      <table style="min-width:640px">
        <thead><tr><th>Insumo</th><th>Cantidad</th><th>Unidad</th><th>Costo total neto</th><th></th></tr></thead>
        <tbody id="lineas"></tbody>
        <tfoot><tr>
          <td><a href="#!" id="agregar" class="btn-flat"><i class="material-icons left">add</i>Agregar línea</a></td>
          <td></td><td class="inv-num"><strong>Total</strong></td><td class="inv-num"><strong id="total">$0</strong> <span id="ref" class="grey-text"></span></td><td></td>
        </tr></tfoot>
      </table>
    </div>
    <button class="btn waves-effect" type="submit"><i class="material-icons left">save</i>Registrar recepción</button>
  </form>

  <template id="tpl-linea">
    <tr>
      <td><select class="browser-default js-insumo" required>
        <option value="">Insumo…</option>
        @foreach($insumos as $i)<option value="{{ $i->id }}" data-um="{{ $i->id_unidad_medida }}">{{ $i->nombre }}</option>@endforeach
      </select></td>
      <td><input type="number" step="any" min="0" class="js-cant" required style="width:100px;margin:0"></td>
      <td><select class="browser-default js-um" required>
        @foreach($unidades as $u)<option value="{{ $u->id }}">{{ $u->abreviatura }}</option>@endforeach
      </select></td>
      <td><input type="number" step="any" min="0" class="js-costo" required style="width:120px;margin:0"></td>
      <td><a href="#!" class="js-quitar" title="Quitar"><i class="material-icons red-text">close</i></a></td>
    </tr>
  </template>
</div>
@endsection

@section('foot')
<script>
(function () {
  var tbody = document.getElementById('lineas'), tpl = document.getElementById('tpl-linea'), n = 0;
  function renombrar() {
    Array.prototype.forEach.call(tbody.rows, function (tr, i) {
      tr.querySelector('.js-insumo').name = 'lineas[' + i + '][id_insumo]';
      tr.querySelector('.js-cant').name = 'lineas[' + i + '][cantidad]';
      tr.querySelector('.js-um').name = 'lineas[' + i + '][id_unidad_medida]';
      tr.querySelector('.js-costo').name = 'lineas[' + i + '][costo_total]';
    });
  }
  function total() {
    var t = 0;
    tbody.querySelectorAll('.js-costo').forEach(function (c) { t += parseFloat(c.value || 0); });
    document.getElementById('total').textContent = '$' + Math.round(t).toLocaleString('es-CL');
    var sel = document.getElementById('egreso'), opt = sel.options[sel.selectedIndex];
    var ref = opt && opt.dataset.neto ? parseInt(opt.dataset.neto, 10) : null;
    document.getElementById('ref').textContent = ref !== null ? '(factura: $' + ref.toLocaleString('es-CL') + ' neto)' : '';
  }
  function agregar() {
    var tr = tpl.content.firstElementChild.cloneNode(true);
    tr.querySelector('.js-insumo').addEventListener('change', function (e) {
      var um = e.target.options[e.target.selectedIndex].dataset.um;
      if (um) tr.querySelector('.js-um').value = um;
    });
    tr.querySelector('.js-costo').addEventListener('input', total);
    tr.querySelector('.js-quitar').addEventListener('click', function (e) { e.preventDefault(); tr.remove(); renombrar(); total(); });
    tbody.appendChild(tr); renombrar();
  }
  document.getElementById('agregar').addEventListener('click', function (e) { e.preventDefault(); agregar(); });
  document.getElementById('egreso').addEventListener('change', total);
  agregar(); total();
})();
</script>
@endsection
