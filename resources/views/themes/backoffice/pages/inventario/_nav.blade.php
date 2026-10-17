{{-- Navegación común del inventario + mensajes --}}
<style>
  .inv-nav { display:flex; flex-wrap:wrap; gap:6px; margin:6px 0 14px; }
  .inv-nav a { padding:6px 12px; border-radius:16px; background:#eceff1; color:#37474f; font-size:.88rem; }
  .inv-nav a.activo { background:#00695c; color:#fff; }
  .inv-msg { padding:10px 14px; border-radius:6px; margin-bottom:12px; font-size:.92rem; }
  .inv-msg.ok { background:#e8f5e9; color:#1b5e20; }
  .inv-msg.err { background:#ffebee; color:#b71c1c; }
  .inv-num { text-align:right; font-variant-numeric: tabular-nums; white-space:nowrap; }
</style>
@php $r = Route::currentRouteName(); @endphp
<div class="inv-nav">
  <a href="{{ route('backoffice.inventario.index') }}" class="{{ $r === 'backoffice.inventario.index' ? 'activo' : '' }}">Stock</a>
  <a href="{{ route('backoffice.inventario.toma.create') }}" class="{{ $r === 'backoffice.inventario.toma.create' ? 'activo' : '' }}">Toma de inventario</a>
  <a href="{{ route('backoffice.inventario.compras.lista') }}" class="{{ $r === 'backoffice.inventario.compras.lista' ? 'activo' : '' }}">Lista de compras</a>
  <a href="{{ route('backoffice.inventario.compras.index') }}" class="{{ \Illuminate\Support\Str::startsWith($r, 'backoffice.inventario.compras.') && ! in_array($r, ['backoffice.inventario.compras.lista', 'backoffice.inventario.compras.configurar']) ? 'activo' : '' }}">Pedidos</a>
  <a href="{{ route('backoffice.inventario.recibidas.index') }}" class="{{ \Illuminate\Support\Str::startsWith($r, 'backoffice.inventario.recibidas.') ? 'activo' : '' }}">Facturas recibidas</a>
  <a href="{{ route('backoffice.inventario.facturas.index') }}" class="{{ \Illuminate\Support\Str::startsWith($r, 'backoffice.inventario.facturas.') ? 'activo' : '' }}">Facturas por foto</a>
  <a href="{{ route('backoffice.inventario.recepcion.create') }}" class="{{ $r === 'backoffice.inventario.recepcion.create' ? 'activo' : '' }}">Recepción sin pedido</a>
  <a href="{{ route('backoffice.inventario.movimientos') }}" class="{{ $r === 'backoffice.inventario.movimientos' ? 'activo' : '' }}">Movimientos</a>
  <a href="{{ route('backoffice.inventario.compras.configurar') }}" class="{{ $r === 'backoffice.inventario.compras.configurar' ? 'activo' : '' }}">Configurar insumos</a>
  <a href="{{ route('backoffice.inventario.recetas') }}" class="{{ $r === 'backoffice.inventario.recetas' ? 'activo' : '' }}">Recetas con problemas</a>
</div>
@if(session('success'))<div class="inv-msg ok">{{ session('success') }}</div>@endif
@if(session('error'))<div class="inv-msg err">{{ session('error') }}</div>@endif
@if(isset($errors) && $errors->any())
  <div class="inv-msg err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif
