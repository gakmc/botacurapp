@extends('themes.backoffice.layouts.admin')

@section('title', 'Inventario')

@section('content')
<div class="section">
  <p class="caption"><strong>Inventario de insumos</strong></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  <div class="row" style="margin-bottom:0">
    <div class="col s12 m4"><div class="card-panel" style="padding:12px 14px">
      <span class="grey-text" style="font-size:.75rem;text-transform:uppercase">Valor en bodega (neto)</span>
      <div style="font-size:1.5rem;font-weight:700">${{ number_format($totalValor, 0, ',', '.') }}</div>
    </div></div>
    <div class="col s6 m4"><div class="card-panel" style="padding:12px 14px">
      <span class="grey-text" style="font-size:.75rem;text-transform:uppercase">Bajo stock crítico</span>
      <div style="font-size:1.5rem;font-weight:700;color:{{ $criticos ? '#c62828' : '#2e7d32' }}">{{ $criticos }}</div>
    </div></div>
    <div class="col s6 m4"><div class="card-panel" style="padding:12px 14px">
      <span class="grey-text" style="font-size:.75rem;text-transform:uppercase">Sin toma inicial</span>
      <div style="font-size:1.5rem;font-weight:700;color:{{ $sinIniciar ? '#ef6c00' : '#2e7d32' }}">{{ $sinIniciar }}</div>
    </div></div>
  </div>

  @if($sinIniciar)
    <div class="inv-msg" style="background:#fff3e0;color:#e65100">
      {{ $sinIniciar }} insumo(s) todavía no tienen toma inicial: su stock no se mueve hasta que se cuenten.
      <a href="{{ route('backoffice.inventario.toma.create', ['sector_id' => $sectorId]) }}"><strong>Hacer toma de inventario</strong></a>
    </div>
  @endif

  <form method="GET" action="{{ route('backoffice.inventario.index') }}">
    <div class="row" style="margin-bottom:0">
      <div class="col s12 m4"><div class="input-field">
        <input id="search" name="search" type="text" value="{{ $search }}"><label for="search" class="active">Buscar insumo</label>
      </div></div>
      <div class="col s12 m3"><div class="input-field">
        <select name="id_proveedor" class="browser-default" onchange="this.form.submit()">
          <option value="">Todos los proveedores</option>
          <option value="sin" {{ $provId === 'sin' ? 'selected' : '' }}>Sin proveedor</option>
          @foreach($proveedoresFiltro as $p)<option value="{{ $p->id }}" {{ (string) $provId === (string) $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>@endforeach
        </select>
      </div></div>
      <div class="col s12 m3"><div class="input-field">
        <select name="sector_id" class="browser-default" onchange="this.form.submit()">
          <option value="">Todos los sectores</option>
          @foreach($sectores as $s)<option value="{{ $s->id }}" {{ (string)$sectorId === (string)$s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>@endforeach
        </select>
      </div></div>
      <div class="col s12 m2" style="margin-top:22px"><button class="btn waves-effect" type="submit"><i class="material-icons left">search</i>Filtrar</button></div>
    </div>
  </form>

  <div class="card-panel" style="overflow-x:auto">
    <table class="highlight" style="min-width:720px">
      <thead><tr>
        <th>Insumo</th><th>Ubicación</th><th class="inv-num">Stock</th><th class="inv-num">Crítico</th>
        <th class="inv-num">Costo prom. (neto)</th><th class="inv-num">Valor stock</th><th>Estado</th><th></th>
      </tr></thead>
      <tbody>
      @php $seccion = null; @endphp
      @foreach($insumos as $i)
        @php
          $um = optional($i->unidadMedida)->abreviatura;
          $fmt = function ($n) { return rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ','); };
          $critico = $i->iniciado && (float) $i->cantidad <= (float) $i->stock_critico;
          $nombreSeccion = optional($i->proveedor)->nombre ?: 'Sin proveedor asignado';
        @endphp
        @if($nombreSeccion !== $seccion)
          @php $seccion = $nombreSeccion; @endphp
          <tr style="background:#eceff1"><td colspan="8" style="font-weight:700;color:#37474f">{{ $seccion }}</td></tr>
        @endif
        <tr>
          <td>{{ $i->nombre }}</td>
          <td class="grey-text">{{ $i->ubicacion ?: optional($i->sector)->nombre }}</td>
          <td class="inv-num">{{ $i->iniciado ? $fmt($i->cantidad) . ' ' . $um : '—' }}</td>
          <td class="inv-num">{{ $fmt($i->stock_critico) }} {{ $um }}</td>
          <td class="inv-num">${{ number_format($i->valor, 0, ',', '.') }} / {{ $um }}</td>
          <td class="inv-num">{{ $i->iniciado ? '$' . number_format($i->valor_stock, 0, ',', '.') : '—' }}</td>
          <td>
            @if(! $i->iniciado)<span class="new badge orange" data-badge-caption="Sin toma"></span>
            @elseif((float) $i->cantidad < 0)<span class="new badge red" data-badge-caption="Negativo"></span>
            @elseif($critico)<span class="new badge red" data-badge-caption="Crítico"></span>
            @else<span class="new badge green" data-badge-caption="OK"></span>@endif
          </td>
          <td><a href="{{ route('backoffice.inventario.kardex', $i->id) }}" title="Ver movimientos"><i class="material-icons">history</i></a></td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>

  {{-- Merma rápida --}}
  <div class="card-panel">
    <p style="font-weight:600;margin-top:0">Registrar merma (rotura, vencimiento, pérdida)</p>
    <form method="POST" action="{{ route('backoffice.inventario.merma.store') }}">
      @csrf
      <div class="row" style="margin-bottom:0">
        <div class="col s12 m4"><select name="id_insumo" class="browser-default" required>
          <option value="">Insumo…</option>
          @foreach($insumos as $i)<option value="{{ $i->id }}" data-um="{{ $i->id_unidad_medida }}">{{ $i->nombre }} ({{ optional($i->unidadMedida)->abreviatura }})</option>@endforeach
        </select></div>
        <div class="col s6 m2"><input type="number" step="any" min="0" name="cantidad" placeholder="Cantidad" required></div>
        <div class="col s6 m2"><select name="id_unidad_medida" class="browser-default" required>
          @foreach(DB::table('unidades_medidas')->orderBy('id')->get() as $u)<option value="{{ $u->id }}">{{ $u->abreviatura }}</option>@endforeach
        </select></div>
        <div class="col s12 m3"><input type="text" name="observacion" maxlength="255" placeholder="Motivo" required></div>
        <div class="col s12 m1"><button class="btn red lighten-1" type="submit">Restar</button></div>
      </div>
    </form>
  </div>
</div>
@endsection

@section('foot')
<script>
  // Al elegir insumo en merma, proponer su unidad
  document.querySelectorAll('select[name="id_insumo"]').forEach(function (sel) {
    sel.addEventListener('change', function () {
      var um = sel.options[sel.selectedIndex].getAttribute('data-um');
      var u = sel.form.querySelector('select[name="id_unidad_medida"]');
      if (um && u) u.value = um;
    });
  });
</script>
@endsection
