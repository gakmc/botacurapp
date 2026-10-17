@extends('themes.backoffice.layouts.admin')

@section('title', 'Pedidos de compra')

@section('content')
<div class="section">
  <p class="caption"><strong>Pedidos de compra</strong></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  <style>
    .chip-est { display:inline-block; padding:4px 10px; border-radius:12px; font-size:.8rem; font-weight:600; }
    .est-por_comprar { background:#fff3e0; color:#e65100; }
    .est-comprado { background:#e3f2fd; color:#0d47a1; }
    .est-recibido { background:#e8f5e9; color:#1b5e20; }
    .est-con_diferencias { background:#ffebee; color:#b71c1c; }
    .est-cancelado { background:#eceff1; color:#546e7a; }
  </style>

  <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px">
    <a href="{{ route('backoffice.inventario.compras.index') }}" class="chip-est" style="background:{{ $estado ? '#eceff1' : '#00695c' }};color:{{ $estado ? '#37474f' : '#fff' }}">Activos</a>
    @foreach($estados as $k => $v)
      <a href="{{ route('backoffice.inventario.compras.index', ['estado' => $k]) }}" class="chip-est est-{{ $k }}" style="{{ $estado === $k ? 'outline:2px solid currentColor' : '' }}">{{ $v }} ({{ $conteo[$k] ?? 0 }})</a>
    @endforeach
  </div>

  @if($facturasSinRecepcion->isNotEmpty())
    <div class="inv-msg" style="background:#fff8e1;color:#795548">
      <strong>{{ $facturasSinRecepcion->count() }} factura(s) de proveedores de insumos sin recepción registrada</strong> (últimos 21 días).
      ¿Llegó la mercadería y nadie la contó?
      <ul style="margin:6px 0 0 18px;list-style:disc">
        @foreach($facturasSinRecepcion->take(10) as $f)
          <li>{{ \Carbon\Carbon::parse($f->fecha_egreso)->format('d/m') }} · {{ optional($f->proveedor)->nombre }} · folio {{ $f->numero_documento }} · neto ${{ number_format((int) $f->total - (int) $f->iva, 0, ',', '.') }}
            · <a href="{{ route('backoffice.inventario.recepcion.create', ['egreso_id' => $f->id]) }}">registrar recepción</a></li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card-panel" style="overflow-x:auto">
    @if($pedidos->isEmpty())
      <p class="center grey-text">No hay pedidos. Créalos desde la <a href="{{ route('backoffice.inventario.compras.lista') }}">lista de compras</a>.</p>
    @else
    <table class="highlight" style="min-width:760px">
      <thead><tr><th>#</th><th>Proveedor</th><th>Estado</th><th class="inv-num">Ítems</th><th>Pedido</th><th>Compra</th><th>Recepción</th><th></th></tr></thead>
      <tbody>
      @foreach($pedidos as $p)
        <tr>
          <td><a href="{{ route('backoffice.inventario.compras.show', $p) }}">#{{ $p->id }}</a></td>
          <td>{{ $p->nombreProveedor() }}</td>
          <td><span class="chip-est est-{{ $p->estado }}">{{ $estados[$p->estado] ?? $p->estado }}</span></td>
          <td class="inv-num">{{ $p->items->count() }}</td>
          <td style="font-size:.85rem">{{ $p->created_at->format('d/m H:i') }}<br><span class="grey-text">{{ optional($p->creador)->name }}</span></td>
          <td style="font-size:.85rem">@if($p->comprado_at){{ $p->comprado_at->format('d/m H:i') }}<br><span class="grey-text">{{ optional($p->comprador)->name }}</span>@else — @endif</td>
          <td style="font-size:.85rem">@if($p->recibido_at){{ $p->recibido_at->format('d/m H:i') }}<br><span class="grey-text">{{ optional($p->receptor)->name }}</span>@else — @endif</td>
          <td style="white-space:nowrap">
            @if(in_array($p->estado, ['por_comprar', 'comprado']))
              <a class="btn-small waves-effect orange darken-2" href="{{ route('backoffice.inventario.compras.comprar', $p) }}">{{ $p->estado === 'comprado' ? 'Editar compra' : 'Comprar' }}</a>
            @endif
            @if($p->estado === 'comprado')
              <a class="btn-small waves-effect blue darken-2" href="{{ route('backoffice.inventario.compras.recibir', $p) }}">Recibir</a>
            @endif
            <a class="btn-flat btn-small" href="{{ route('backoffice.inventario.compras.show', $p) }}">Ver</a>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
    @endif
  </div>
</div>
@endsection
