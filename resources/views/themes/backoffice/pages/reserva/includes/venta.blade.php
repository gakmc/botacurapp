<div class="collection">


    <a href="" class="collection-item active 
        @if (is_null($reserva->venta))
            ''
        @else 
            {{($reserva->venta->total_pagar === 0) ? 'green' : ''}}
        @endif ">

        <h5>Venta: {{(is_null($reserva->venta)) ? '' : ($reserva->venta->total_pagar === 0) ? 'Pagado' : '' }} </h5>
    </a>


    @if(is_null($reserva->venta))
        <a class="collection-item center">Esta reserva no posee venta. </a>
    @else
        <a class="collection-item center-align valign-wrapper left">
            Abono: {{$reserva->venta->abono_programa}}
        </a>
        <a class="collection-item center-align valign-wrapper left">
            Diferencia: {{(is_null($reserva->venta->diferencia_programa)) ? 'Debe Realizar pago' : $reserva->venta->diferencia_programa}}
        </a>

        <a href="#modalVenta{{--$reserva->venta->id--}}"
            class="collection-item center-align valign-wrapper left modal-trigger" 
            data-id="{{ $reserva->venta->id }}"
            data-abono="{{ $reserva->venta->abono_programa }}"
            data-abonoimg="{{$reserva->venta->imagen_abono ? route('backoffice.reserva.abono.imagen', $reserva->id) : '/images/gallary/no-image.png'}}"
            data-diferencia="{{ $reserva->venta->diferencia_programa }}"
            data-diferenciaimg="{{$reserva->venta->imagen_diferencia ? route('backoffice.reserva.diferencia.imagen', $reserva->id) : '/images/gallary/no-image.png'}}"
            data-descuento="{{$reserva->venta->descuento}}" 
            data-totalpagar="{{$reserva->venta->total_pagar}}"
            data-tipoabono="{{$reserva->venta->tipoTransaccionAbono->nombre ?? 'No registra'}}"
            data-tipodiferencia="{{$reserva->venta->tipoTransaccionDiferencia->nombre ?? 'No registra'}}"
            data-consumo="{{$reserva->venta->consumos}}" 
            
            @foreach ($reserva->venta->consumos as $consumo)
                @if ($consumo->pagosConsumos->where('id_consumo', $consumo->id)->isNotEmpty())
                    data-pagoimg="{{$consumo->pagosConsumos ? route('backoffice.reserva.consumo.imagen', $reserva->id) : null}}"
                @endif
            @endforeach
            >
            <i class='material-icons tooltipped' data-position="bottom" data-tooltip="Ver Venta">remove_red_eye</i>
        </a>

        @if (is_null($reserva->venta->diferencia_programa))
            <a href="{{route('backoffice.reserva.venta.cerrar', ['reserva'=>$reserva, 'ventum'=>$reserva->venta]) }}"
                class="collection-item center-align valign-wrapper left">
                <i class='material-icons tooltipped' data-position="bottom" data-tooltip="Cerrar Venta">attach_money</i>
            </a>

        @else

            <a class="collection-item center-align valign-wrapper left" href="{{ route('backoffice.venta.pdf', $reserva) }}"
                target="_blank">
                <i class="material-icons tooltipped" data-position="bottom" data-tooltip="PDF Venta">picture_as_pdf</i>
            </a>

            @if ($reserva->venta->consumos->isNotEmpty())
                @foreach ($reserva->venta->consumos as $consumo)
                    @if ($consumo->pagosConsumos->where('id_consumo', $consumo->id)->isNotEmpty())
                        <a href="{{ route('backoffice.consumo.pdf', $reserva) }}" target="_blank"
                        class="collection-item center-align valign-wrapper left">
                        <i class="material-icons tooltipped" data-position="bottom" data-tooltip="PDF Consumo">local_bar</i></a>
                    @endif
                @endforeach
            @endif


        @endif

    @endif

</div>