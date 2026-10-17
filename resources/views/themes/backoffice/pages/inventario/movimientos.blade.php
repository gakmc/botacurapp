@extends('themes.backoffice.layouts.admin')

@section('title', 'Movimientos de inventario')

@section('content')
<div class="section">
  <p class="caption"><strong>Movimientos de inventario</strong></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  <form method="GET" action="{{ route('backoffice.inventario.movimientos') }}">
    <div class="row" style="margin-bottom:0">
      <div class="col s6 m3"><label>Desde</label><input type="date" name="desde" value="{{ $desde }}"></div>
      <div class="col s6 m3"><label>Hasta</label><input type="date" name="hasta" value="{{ $hasta }}"></div>
      <div class="col s12 m4"><label>Origen</label>
        <select name="origen" class="browser-default">
          <option value="">Todos</option>
          @foreach($origenes as $k => $v)<option value="{{ $k }}" {{ $origen === $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach
        </select></div>
      <div class="col s12 m2" style="margin-top:22px"><button class="btn waves-effect" type="submit">Ver</button></div>
    </div>
  </form>

  <form method="POST" action="{{ route('backoffice.inventario.conciliar') }}" style="margin:6px 0 12px">
    @csrf
    <button class="btn-flat" type="submit" title="Revisa todas las ventas desde la primera toma y corrige descuentos faltantes o sobrantes">
      <i class="material-icons left">sync</i>Conciliar ventas ahora
    </button>
  </form>

  <div class="card-panel" style="overflow-x:auto">
    @if($movs->isEmpty())
      <p class="center grey-text">No hay movimientos en este período.</p>
    @else
    <table class="highlight" style="min-width:720px">
      <thead><tr><th>Fecha</th><th>Origen</th><th>Detalle</th><th class="inv-num">Costo neto</th><th>Usuario</th></tr></thead>
      <tbody>
      @foreach($movs as $m)
        <tr>
          <td style="white-space:nowrap">{{ \Carbon\Carbon::parse($m->fecha)->format('d/m/Y H:i') }}</td>
          <td>{{ $origenes[$m->origen] ?? $m->origen }}</td>
          <td style="font-size:.88rem">
            @foreach($m->detalles as $d)
              @php $q = (float) $d->cantidad_base; @endphp
              <div><span style="color:{{ $q < 0 ? '#c62828' : '#2e7d32' }}">{{ $q > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($q, 3, ',', '.'), '0'), ',') }}</span>
                {{ optional(optional($d->insumo)->unidadMedida)->abreviatura }} {{ optional($d->insumo)->nombre }}</div>
            @endforeach
            @if($m->observacion)<div class="grey-text">{{ $m->observacion }}</div>@endif
          </td>
          <td class="inv-num">${{ number_format($m->detalles->sum('costo_total'), 0, ',', '.') }}</td>
          <td>{{ optional($m->usuario)->name ?? 'Automático' }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
    {{ $movs->links() }}
    @endif
  </div>
</div>
@endsection
