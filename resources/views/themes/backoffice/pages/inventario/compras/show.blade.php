@extends('themes.backoffice.layouts.admin')

@section('title', 'Pedido #' . $pedido->id)

@section('content')
<div class="section">
  <p class="caption"><strong>Pedido #{{ $pedido->id }} · {{ $pedido->nombreProveedor() }} · {{ $estados[$pedido->estado] ?? $pedido->estado }}</strong></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  @php
    $fmt = function ($n) { return $n === null ? '—' : rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ','); };
    $colores = ['ok' => '#2e7d32', 'no_habia' => '#78909c', 'faltante' => '#c62828', 'no_llego' => '#c62828', 'sobrante' => '#ef6c00', 'extra' => '#ef6c00', 'comprado' => '#0d47a1', 'pendiente' => '#78909c'];
    $costoCompra = $pedido->items->sum(function ($i) { return (float) $i->costo_total_neto; });
  @endphp

  <div class="row" style="margin-bottom:0">
    <div class="col s12 m4"><div class="card-panel" style="padding:12px 14px"><span class="grey-text" style="font-size:.75rem">PEDIDO</span>
      <div>{{ $pedido->created_at->format('d/m/Y H:i') }} · {{ optional($pedido->creador)->name }}</div></div></div>
    <div class="col s12 m4"><div class="card-panel" style="padding:12px 14px"><span class="grey-text" style="font-size:.75rem">COMPRA</span>
      <div>{{ $pedido->comprado_at ? $pedido->comprado_at->format('d/m/Y H:i') . ' · ' . optional($pedido->comprador)->name : 'Pendiente' }}</div></div></div>
    <div class="col s12 m4"><div class="card-panel" style="padding:12px 14px"><span class="grey-text" style="font-size:.75rem">RECEPCIÓN</span>
      <div>{{ $pedido->recibido_at ? $pedido->recibido_at->format('d/m/Y H:i') . ' · ' . optional($pedido->receptor)->name : 'Pendiente' }}</div></div></div>
  </div>

  @if($pedido->egreso)
    @php $netoFactura = (int) $pedido->egreso->total - (int) $pedido->egreso->iva; @endphp
    <div class="inv-msg" style="background:#f3e5f5;color:#4a148c">
      Factura folio {{ $pedido->egreso->numero_documento }} del {{ \Carbon\Carbon::parse($pedido->egreso->fecha_egreso)->format('d/m/Y') }}: neto ${{ number_format($netoFactura, 0, ',', '.') }}.
      @if($costoCompra > 0)
        Costo anotado en la compra: ${{ number_format($costoCompra, 0, ',', '.') }}
        @if(abs($netoFactura - $costoCompra) > 1) <strong>(diferencia ${{ number_format($netoFactura - $costoCompra, 0, ',', '.') }})</strong>@endif
      @endif
    </div>
  @endif

  <div class="card-panel" style="overflow-x:auto">
    <table class="highlight" style="min-width:760px">
      <thead><tr><th>Insumo</th><th class="inv-num">Pedido</th><th class="inv-num">Comprado</th><th class="inv-num">Costo neto</th><th class="inv-num">Recibido</th><th>Estado</th><th>Notas</th></tr></thead>
      <tbody>
      @foreach($pedido->items as $it)
        @php $um = optional(optional($it->insumo)->unidadMedida)->abreviatura; @endphp
        <tr>
          <td>{{ optional($it->insumo)->nombre }}</td>
          <td class="inv-num">{{ $it->estado === 'extra' ? '—' : $fmt($it->cantidad_pedida) . ' ' . $um }}</td>
          <td class="inv-num">{{ $fmt($it->cantidad_comprada) }} {{ $it->cantidad_comprada !== null ? $um : '' }}</td>
          <td class="inv-num">{{ $it->costo_total_neto !== null ? '$' . number_format($it->costo_total_neto, 0, ',', '.') : '—' }}</td>
          <td class="inv-num">{{ $fmt($it->cantidad_recibida) }} {{ $it->cantidad_recibida !== null ? $um : '' }}</td>
          <td><strong style="color:{{ $colores[$it->estado] ?? '#37474f' }}">{{ $estadosItem[$it->estado] ?? $it->estado }}</strong></td>
          <td class="grey-text" style="font-size:.85rem">{{ $it->nota }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>

  <div style="display:flex;gap:8px;flex-wrap:wrap">
    @if(in_array($pedido->estado, ['por_comprar', 'comprado']))
      <a class="btn waves-effect orange darken-2" href="{{ route('backoffice.inventario.compras.comprar', $pedido) }}">{{ $pedido->estado === 'comprado' ? 'Editar compra' : 'Comprar' }}</a>
    @endif
    @if($pedido->estado === 'comprado')
      <a class="btn waves-effect blue darken-2" href="{{ route('backoffice.inventario.compras.recibir', $pedido) }}">Recibir</a>
    @endif
    <a class="btn-flat" href="{{ route('backoffice.inventario.compras.index') }}">Volver a pedidos</a>
  </div>
</div>
@endsection
