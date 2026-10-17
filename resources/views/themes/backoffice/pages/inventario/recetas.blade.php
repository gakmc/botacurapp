@extends('themes.backoffice.layouts.admin')

@section('title', 'Recetas con problemas')

@section('content')
<div class="section">
  <p class="caption"><strong>Recetas con problemas</strong></p>
  <div class="divider"></div>
  @include('themes.backoffice.pages.inventario._nav')

  <p style="max-width:70ch;color:#546e7a">
    Estas líneas de receta no se pueden descontar del inventario hasta corregirlas en la ficha del producto.
    Las unidades deben ser compatibles: peso con peso (kg, g), volumen con volumen (lt, ml), o exactamente la misma unidad.
  </p>

  <div class="card-panel" style="overflow-x:auto">
    <p style="font-weight:600;margin-top:0">Líneas de receta que no se pueden descontar ({{ count($lineas) }})</p>
    @if(! count($lineas))
      <p class="green-text">No hay problemas.</p>
    @else
    <table class="highlight" style="min-width:560px">
      <thead><tr><th>Producto</th><th>Insumo</th><th class="inv-num">Cantidad</th><th>Problema</th><th></th></tr></thead>
      <tbody>
      @foreach($lineas as $l)
        <tr>
          <td>{{ $l['producto'] }}</td>
          <td>{{ $l['insumo'] ?? '—' }}</td>
          <td class="inv-num">{{ $l['cantidad_insumo_usar'] }}</td>
          <td class="red-text text-darken-2">{{ $l['motivo'] }}</td>
          <td><a href="{{ route('backoffice.producto.edit', $l['id_producto']) }}" title="Editar producto"><i class="material-icons">mode_edit</i></a></td>
        </tr>
      @endforeach
      </tbody>
    </table>
    @endif
  </div>

  <div class="card-panel" style="overflow-x:auto">
    <p style="font-weight:600;margin-top:0">Productos vendidos sin receta desde el {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} ({{ count($sin_receta) }})</p>
    <p class="grey-text" style="font-size:.88rem;margin-top:0">No descuentan ningún insumo. Si son servicios o entradas, está bien; si son comida o bebida, conviene agregarles receta.</p>
    <table class="highlight" style="min-width:400px">
      <thead><tr><th>Producto</th><th class="inv-num">Unidades vendidas</th><th></th></tr></thead>
      <tbody>
      @foreach($sin_receta as $p)
        <tr>
          <td>{{ $p->nombre }}</td>
          <td class="inv-num">{{ number_format($p->vendidos, 0, ',', '.') }}</td>
          <td><a href="{{ route('backoffice.producto.edit', $p->id) }}" title="Agregar receta"><i class="material-icons">mode_edit</i></a></td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
