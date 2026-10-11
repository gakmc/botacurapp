@extends('themes.backoffice.layouts.admin')

@section('title', 'Facturas recibidas')

@section('content')
<div class="section">
  <p class="caption"><strong>Facturas recibidas</strong></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  <p style="max-width:75ch;color:#546e7a">
    Facturas con detalle de productos, del SII (XML) o leídas de foto. Entra a cada una para elegir qué productos pasan al inventario.
    Si un proveedor entrega la factura impresa, <a href="{{ route('backoffice.inventario.facturas.create') }}">súbela por foto</a>.
  </p>

  <form method="GET" action="{{ route('backoffice.inventario.recibidas.index') }}">
    <div class="row" style="margin-bottom:0">
      <div class="col s12 m4"><label>Proveedor o folio</label><input type="text" name="buscar" value="{{ $buscar }}"></div>
      <div class="col s6 m3"><label>Desde</label><input type="date" name="desde" value="{{ $desde }}"></div>
      <div class="col s6 m3"><label>Mostrar</label>
        <select name="estado" class="browser-default">
          <option value="pendientes" {{ $estado === 'pendientes' ? 'selected' : '' }}>Con productos por revisar</option>
          <option value="todas" {{ $estado === 'todas' ? 'selected' : '' }}>Todas</option>
        </select></div>
      <div class="col s12 m2" style="margin-top:22px"><button class="btn waves-effect" type="submit">Ver</button></div>
    </div>
  </form>

  <div class="card-panel" style="overflow-x:auto">
    @if($facturas->isEmpty())
      <p class="center grey-text">No hay facturas en este filtro.</p>
    @else
    <table class="highlight" style="min-width:720px">
      <thead><tr><th>Fecha</th><th>Proveedor</th><th>Folio</th><th>Origen</th><th class="inv-num">Neto</th><th class="inv-num">Productos</th><th>Inventario</th></tr></thead>
      <tbody>
      @foreach($facturas as $f)
        <tr>
          <td>{{ \Carbon\Carbon::parse($f->fecha)->format('d/m/Y') }}</td>
          <td><a href="{{ route('backoffice.inventario.recibidas.show', [$f->rut_emisor, $f->dte, $f->folio]) }}">{{ $f->razon_social }}</a></td>
          <td>{{ $f->folio }}</td>
          <td>{{ $f->origen === 'foto' ? 'Foto' : 'XML' }}</td>
          <td class="inv-num">${{ number_format($f->neto, 0, ',', '.') }}</td>
          <td class="inv-num">{{ $f->lineas }}</td>
          <td style="font-size:.88rem">
            @if($f->agregadas)<span class="green-text">{{ $f->agregadas }} en inventario</span>@endif
            @if($f->omitidas) <span class="grey-text">· {{ $f->omitidas }} no inventario</span>@endif
            @if($f->pendientes) <span class="orange-text">· {{ $f->pendientes }} por revisar</span>@endif
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
    @endif
  </div>
</div>
@endsection
