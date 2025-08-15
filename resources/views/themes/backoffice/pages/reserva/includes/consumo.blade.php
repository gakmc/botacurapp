<div class="collection">


    <a href="" class="collection-item active" style="flex-basis: 100%">
        <h5>Consumo:</h5>
    </a>

    @if(is_null($reserva->venta->consumo))
    <a class="collection-item center">Esta cuenta no posee consumos. </a>
    <a id="btn-servicio" href="{{ route('backoffice.venta.consumo.service_create', $reserva->venta) }}"
        class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right tooltipped"
        data-position="bottom" data-tooltip="Agregar Servicio">
        <i class="material-icons">hot_tub</i>
    </a>
    <a id="btn-producto" href="{{ route('backoffice.venta.consumo.create', $reserva->venta) }}"
        class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right mr-5 tooltipped"
        data-position="bottom" data-tooltip="Agregar Producto">
        <i class="material-icons">local_bar</i>
    </a>
    @else
    <div style="display: flex; width: 100%; flex-direction:column;">

        @foreach ($reserva->venta->consumo->detallesConsumos as $detalle)


            {{-- <a class="collection-item center-align valign-wrapper">
                {{$detalle->producto->nombre}} - Cantidad: {{$detalle->cantidad_producto}}<a class="btn-small"><i class="material-icons">close</i></a> 
            </a> --}}

            <div class="valign-wrapper" style="margin: 4px 0;">
                @if (Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.jefe_local_role')))
                <a id="icono-eliminar" href="#" class="btn-flat pink-text btn-eliminar-detalle" style="padding: 0; margin-right: 10px;" data-url="{{route('backoffice.consumo.detalle.destroy', ['tipo'=>'consumo', 'id' => $detalle->id])}}">
                    <i class="material-icons">close</i>
                </a>

                @else
                <div style="padding: 0; margin-right: 10px;"></div>
                @endif

                <span class="pink-text" style="font-weight: 500;">
                    {{$detalle->producto->nombre}} - Cantidad: {{$detalle->cantidad_producto}}
                </span>
            </div>
            
            
            
        

        @endforeach

        @foreach ($reserva->venta->consumo->detalleServiciosExtra as $detalle)


            {{-- <a class="collection-item center-align valign-wrapper">
                <i class='material-icons left'>close</i>{{$detalle->servicio->nombre_servicio}} - Cantidad: {{$detalle->cantidad_servicio}}
            </a> --}}

            <div class="valign-wrapper" style="margin: 4px 0;">

                @if (Auth::user()->has_role(config('app.admin_role')) || Auth::user()->has_role(config('app.jefe_local_role')))
                    
                <a id="icono-eliminar" href="#" class="btn-flat pink-text btn-eliminar-detalle" style="padding: 0; margin-right: 10px;" data-url="{{route('backoffice.consumo.detalle.destroy', ['tipo'=>'servicio', 'id' => $detalle->id])}}">
                    <i class="material-icons">close</i>
                </a>

                @else
                <div style="padding: 0; margin-right: 10px;"></div>
                @endif

                <span class="pink-text" style="font-weight: 500;">
                    {{$detalle->servicio->nombre_servicio}} - Cantidad: {{$detalle->cantidad_servicio}}
                </span>
            </div>
            

        
        @endforeach

</div>
    <a id="btn-servicio" href="{{ route('backoffice.venta.consumo.service_create', $reserva->venta) }}"
        class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right tooltipped"
        data-position="bottom" data-tooltip="Agregar Servicio">
        <i class="material-icons">hot_tub</i>
    </a>
    <a id="btn-producto" href="{{ route('backoffice.venta.consumo.create', $reserva->venta) }}"
        class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right mr-5 tooltipped"
        data-position="bottom" data-tooltip="Agregar Producto">
        <i class="material-icons">local_bar</i>
    </a>
    @endif
</div>


<form id="form-eliminar-detalle" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-eliminar-detalle').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const url = this.dataset.url;
    
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción no se puede deshacer",
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#aaa',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('form-eliminar-detalle');
                        form.setAttribute('action', url);
                        form.submit();
                    }
                });
            });
        });
    });
</script>
    
    
