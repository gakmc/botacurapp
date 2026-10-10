@extends('themes.backoffice.layouts.admin')

@section('title', 'Comprar pedido #' . $pedido->id)

@section('content')
<div class="section">
  <p class="caption"><strong>Comprar · pedido #{{ $pedido->id }} · {{ $pedido->nombreProveedor() }}</strong></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  <p style="max-width:70ch;color:#546e7a">
    Anota cuánto compraste de cada cosa (si dejas vacío se asume lo pedido) y, si lo sabes, el costo total neto de esa línea.
    Si no había, marca <strong>No había</strong>. No borres nada: así queda claro qué se pidió y qué se consiguió.
  </p>
  @if($pedido->observacion)<div class="inv-msg" style="background:#e3f2fd;color:#0d47a1">Nota del pedido: {{ $pedido->observacion }}</div>@endif

  <form method="POST" action="{{ route('backoffice.inventario.compras.comprar.store', $pedido) }}">
    @csrf
    <style>
      .linea-compra { display:grid; grid-template-columns: minmax(160px,2fr) 1fr 1fr 1fr auto; gap:10px; align-items:center; padding:10px 0; border-bottom:1px solid #eceff1; }
      @media (max-width: 700px) { .linea-compra { grid-template-columns: 1fr 1fr; } .linea-compra .nombre { grid-column: 1 / -1; } }
      .linea-compra input { margin:0 !important; }
      .linea-compra.no-habia { opacity:.5; }
    </style>
    <div class="card-panel">
      @foreach($pedido->items as $it)
        @php $um = optional(optional($it->insumo)->unidadMedida)->abreviatura; $fmt = rtrim(rtrim(number_format((float) $it->cantidad_pedida, 3, ',', '.'), '0'), ','); @endphp
        <div class="linea-compra {{ $it->estado === 'no_habia' ? 'no-habia' : '' }}">
          <div class="nombre"><strong>{{ optional($it->insumo)->nombre }}</strong><br><span class="grey-text">Pedido: {{ $fmt }} {{ $um }} {{ optional($it->insumo)->presentacion ? '· ' . $it->insumo->presentacion : '' }}</span></div>
          <div><label>Compré ({{ $um }})</label><input type="number" step="any" min="0" name="item[{{ $it->id }}][comprada]" placeholder="{{ $fmt }}" value="{{ $it->cantidad_comprada !== null && $it->estado !== 'no_habia' ? (float) $it->cantidad_comprada : '' }}"></div>
          <div><label>Costo total neto $</label><input type="number" step="any" min="0" name="item[{{ $it->id }}][costo]" value="{{ $it->costo_total_neto !== null ? (float) $it->costo_total_neto : '' }}"></div>
          <div><label>Nota</label><input type="text" maxlength="200" name="item[{{ $it->id }}][nota]" value="{{ $it->nota }}"></div>
          <div><label><input type="checkbox" class="filled-in js-nohabia" name="item[{{ $it->id }}][no_habia]" value="1" {{ $it->estado === 'no_habia' ? 'checked' : '' }}><span>No había</span></label></div>
        </div>
      @endforeach
    </div>
    <button class="btn waves-effect" type="submit"><i class="material-icons left">shopping_cart</i>Registrar compra</button>
    <a href="{{ route('backoffice.inventario.compras.index') }}" class="btn-flat">Volver</a>
  </form>

  @if($pedido->estado === 'por_comprar')
  <form method="POST" action="{{ route('backoffice.inventario.compras.cancelar', $pedido) }}" style="margin-top:18px" onsubmit="return confirm('¿Cancelar este pedido?')">
    @csrf
    <button class="btn-flat red-text" type="submit">Cancelar pedido</button>
  </form>
  @endif
</div>
@endsection

@section('foot')
<script>
  document.querySelectorAll('.js-nohabia').forEach(function (c) {
    c.addEventListener('change', function () { c.closest('.linea-compra').classList.toggle('no-habia', c.checked); });
  });
</script>
@endsection
