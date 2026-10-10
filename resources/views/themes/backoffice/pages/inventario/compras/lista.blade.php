@extends('themes.backoffice.layouts.admin')

@section('title', 'Lista de compras')

@section('content')
<div class="section">
  <p class="caption"><strong>Lista de compras</strong></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  <p style="max-width:75ch;color:#546e7a">
    La cantidad sugerida es <strong>stock objetivo − stock actual − lo ya pedido</strong>. Ajusta lo que quieras y crea los pedidos:
    se genera <strong>uno por proveedor</strong>. Nada se borra: quien compra marcará lo que consiguió y quien recibe contará lo que llegó.
  </p>

  <form method="POST" action="{{ route('backoffice.inventario.compras.crear') }}">
    @csrf
    @php $fmt = function ($n) { return $n === null ? '—' : rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ','); }; @endphp
    @foreach($grupos as $provId => $items)
      @php $conSugerido = collect($items)->where('sugerido', '>', 0)->count(); @endphp
      <div class="card-panel" style="overflow-x:auto;padding:14px 16px">
        <div style="display:flex;justify-content:space-between;align-items:baseline;flex-wrap:wrap;gap:8px">
          <h6 style="margin:0;font-weight:700">{{ $provId ? $proveedores[$provId] : 'Sin proveedor asignado' }}</h6>
          <span class="{{ $conSugerido ? 'orange-text text-darken-3' : 'grey-text' }}" style="font-size:.85rem">
            {{ $conSugerido ? $conSugerido . ' por reponer' : 'Nada por reponer' }} · {{ count($items) }} insumos
            <a href="#!" class="js-toggle" style="margin-left:8px">{{ $conSugerido ? 'ver todos' : 'mostrar' }}</a>
          </span>
        </div>
        <table class="highlight js-grupo" style="min-width:700px;margin-top:8px">
          <thead><tr><th>Insumo</th><th>Ubicación</th><th class="inv-num">Stock</th><th class="inv-num">Objetivo</th><th class="inv-num">Ya pedido</th><th>Pedir</th><th>Presentación</th></tr></thead>
          <tbody>
          @foreach($items as $i)
            @php $um = optional($i->unidadMedida)->abreviatura; @endphp
            <tr class="{{ $i->sugerido > 0 ? '' : 'js-sin' }}" @if($i->sugerido <= 0) style="display:none" @endif>
              <td>{{ $i->nombre }} @if($i->critico)<span class="new badge red" data-badge-caption="Crítico"></span>@endif</td>
              <td class="grey-text">{{ $i->ubicacion }}</td>
              <td class="inv-num">{{ $fmt($i->stock_actual) }} {{ $i->stock_actual !== null ? $um : '' }}</td>
              <td class="inv-num">{{ $fmt($i->stock_objetivo) }}</td>
              <td class="inv-num">{{ $i->en_curso ? $fmt($i->en_curso) . ' ' . $um : '' }}</td>
              <td style="white-space:nowrap"><input type="number" step="any" min="0" name="cant[{{ $i->id }}]" value="{{ $i->sugerido > 0 ? $i->sugerido : '' }}" style="margin:0;width:90px"> {{ $um }}</td>
              <td class="grey-text">{{ $i->presentacion }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    @endforeach

    <div class="row">
      <div class="col s12 m8"><input type="text" name="observacion" maxlength="255" placeholder="Nota para quien compra (opcional)"></div>
      <div class="col s12 m4"><button class="btn waves-effect" type="submit"><i class="material-icons left">playlist_add_check</i>Crear pedidos</button></div>
    </div>
  </form>
</div>
@endsection

@section('foot')
<script>
  document.querySelectorAll('.js-toggle').forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      var tabla = a.closest('.card-panel').querySelector('.js-grupo');
      var ocultas = tabla.querySelectorAll('tr.js-sin');
      var mostrar = ocultas.length && ocultas[0].style.display === 'none';
      ocultas.forEach(function (tr) { tr.style.display = mostrar ? '' : 'none'; });
      a.textContent = mostrar ? 'ocultar los sin reponer' : 'ver todos';
    });
  });
</script>
@endsection
