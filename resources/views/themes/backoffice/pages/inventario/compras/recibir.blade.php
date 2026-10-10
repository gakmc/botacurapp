@extends('themes.backoffice.layouts.admin')

@section('title', 'Recibir pedido #' . $pedido->id)

@section('content')
<div class="section">
  <p class="caption"><strong>Recibir · pedido #{{ $pedido->id }} · {{ $pedido->nombreProveedor() }}</strong></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  <div class="inv-msg" style="background:#e3f2fd;color:#0d47a1;max-width:80ch">
    <strong>Cuenta lo que llegó, producto por producto,</strong> en la unidad indicada. No mostramos lo que se compró a propósito:
    así el conteo es real. Si algo no llegó, anota <strong>0</strong>. Al guardar, la app compara con la compra, marca las diferencias
    y suma lo recibido al inventario.
  </div>

  <form method="POST" action="{{ route('backoffice.inventario.compras.recibir.store', $pedido) }}">
    @csrf
    <style>
      .linea-rec { display:grid; grid-template-columns: minmax(180px,2fr) 1fr 2fr; gap:10px; align-items:center; padding:10px 0; border-bottom:1px solid #eceff1; }
      @media (max-width: 700px) { .linea-rec { grid-template-columns: 1fr 1fr; } .linea-rec .nombre { grid-column: 1 / -1; } }
      .linea-rec input { margin:0 !important; }
    </style>
    <div class="card-panel">
      @foreach($pedido->items->where('estado', '!=', 'extra') as $it)
        @php $um = optional(optional($it->insumo)->unidadMedida)->abreviatura; @endphp
        <div class="linea-rec">
          <div class="nombre"><strong>{{ optional($it->insumo)->nombre }}</strong>
            @if(optional($it->insumo)->presentacion)<br><span class="grey-text">{{ $it->insumo->presentacion }}</span>@endif</div>
          <div><label>Llegó ({{ $um }})</label>
            <input type="number" step="any" min="0" required name="recibida[{{ $it->id }}]" value="{{ old('recibida.' . $it->id) }}"></div>
          <div><label>Observación (dañado, otra marca, vencido…)</label>
            <input type="text" maxlength="200" name="nota[{{ $it->id }}]" value="{{ old('nota.' . $it->id) }}"></div>
        </div>
      @endforeach

      <p style="font-weight:600;margin:16px 0 4px">¿Llegó algo que no estaba en el pedido?</p>
      <div id="extras"></div>
      <a href="#!" id="agregar-extra" class="btn-flat"><i class="material-icons left">add</i>Agregar producto no pedido</a>
    </div>

    <div class="row">
      <div class="col s12 m8"><label>Factura SII de esta compra (si llegó con factura)</label>
        <select name="egreso_id" class="browser-default">
          <option value="">Sin factura / boleta / aún no llega</option>
          @foreach($facturas as $f)
            <option value="{{ $f->id }}">{{ \Carbon\Carbon::parse($f->fecha_egreso)->format('d/m') }} · folio {{ $f->numero_documento }} · neto ${{ number_format((int) $f->total - (int) $f->iva, 0, ',', '.') }}</option>
          @endforeach
        </select></div>
      <div class="col s12 m4" style="margin-top:22px"><button class="btn waves-effect" type="submit"><i class="material-icons left">fact_check</i>Confirmar recepción</button></div>
    </div>
  </form>

  <template id="tpl-extra">
    <div class="linea-rec">
      <div class="nombre"><select class="browser-default js-ins">
        <option value="">Producto…</option>
        @foreach($insumos as $i)<option value="{{ $i->id }}">{{ $i->nombre }} ({{ optional($i->unidadMedida)->abreviatura }})</option>@endforeach
      </select></div>
      <div><input type="number" step="any" min="0" class="js-cant" placeholder="Cantidad"></div>
      <div><input type="text" maxlength="200" class="js-nota" placeholder="Observación"></div>
    </div>
  </template>
</div>
@endsection

@section('foot')
<script>
  (function () {
    var n = 0, cont = document.getElementById('extras'), tpl = document.getElementById('tpl-extra');
    document.getElementById('agregar-extra').addEventListener('click', function (e) {
      e.preventDefault();
      var el = tpl.content.firstElementChild.cloneNode(true);
      el.querySelector('.js-ins').name = 'extra[' + n + '][id_insumo]';
      el.querySelector('.js-cant').name = 'extra[' + n + '][cantidad]';
      el.querySelector('.js-nota').name = 'extra[' + n + '][nota]';
      n++; cont.appendChild(el);
    });
  })();
</script>
@endsection
