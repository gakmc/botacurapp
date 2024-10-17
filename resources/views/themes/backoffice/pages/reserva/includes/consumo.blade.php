<div class="collection">


    <a href="" class="collection-item active" style="flex-basis: 100%">

        <h5>Consumo:</h5>
    </a>

    @if(is_null($reserva->venta->consumos))
    <a class="collection-item center">Esta cuenta no posee consumos. </a>
    <a href="{{ route('backoffice.venta.consumo.service_create', $reserva->venta) }}"
        class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right tooltipped"
        data-position="bottom" data-tooltip="Agregar Servicio">
        <i class="material-icons">hot_tub</i>
    </a>
    <a href="{{ route('backoffice.venta.consumo.create', $reserva->venta) }}"
        class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right mr-5 tooltipped"
        data-position="bottom" data-tooltip="Agregar Producto">
        <i class="material-icons">local_bar</i>
    </a>
    @else
    <div style="display: flex; width: 100%; flex-direction:column;">
    @foreach($reserva->venta->consumos as $consumo)
    @foreach ($consumo->detallesConsumos as $detalle)


    <a class="collection-item center-align valign-wrapper">
        {{$detalle->producto->nombre}} - Cantidad: {{$detalle->cantidad_producto}}
    </a>
    

    @endforeach

    @foreach ($consumo->detalleServiciosExtra as $detalle)


    <a class="collection-item center-align valign-wrapper">
        {{$detalle->servicio->nombre_servicio}} - Cantidad: {{$detalle->cantidad_servicio}}
    </a>

    
    @endforeach

    @endforeach
</div>
    <a href="{{ route('backoffice.venta.consumo.service_create', $reserva->venta) }}"
        class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right tooltipped"
        data-position="bottom" data-tooltip="Agregar Servicio">
        <i class="material-icons">hot_tub</i>
    </a>
    <a href="{{ route('backoffice.venta.consumo.create', $reserva->venta) }}"
        class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right mr-5 tooltipped"
        data-position="bottom" data-tooltip="Agregar Producto">
        <i class="material-icons">local_bar</i>
    </a>
    @endif
</div>