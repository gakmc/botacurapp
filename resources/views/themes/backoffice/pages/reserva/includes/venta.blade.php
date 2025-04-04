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
        @php
            $consumo = $reserva->venta->consumo;
        @endphp
        <a class="collection-item center-align valign-wrapper left">
            Abono: {{$reserva->venta->abono_programa}}
        </a>
        <a class="collection-item center-align valign-wrapper left">
            Diferencia: {{(is_null($reserva->venta->diferencia_programa)) ? 'Debe Realizar pago' : $reserva->venta->diferencia_programa}}
        </a>

        <a href="#modalVenta{{--$reserva->venta->id--}}"
            class="collection-item center-align valign-wrapper left modal-trigger" 
            data-id="{{ $reserva->venta->id }}"

            data-diferencia="{{ $reserva->venta->diferencia_programa }}"

            data-totalpagar="{{$reserva->venta->total_pagar}}"


            data-consumo="{{$consumo}}" 
            
                {{-- @if (!is_null($consumo) && !is_null($consumo->pagosConsumos) && $consumo->pagosConsumos->where('id_consumo', $consumo->id)->isNotEmpty())
                    data-pagoimg="{{$reserva->venta->consumo->pagosConsumos ? route('backoffice.reserva.consumo.imagen', $reserva->id) : null}}"
                @endif --}}

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

            @if (is_null($consumo))
                {{-- @foreach ($reserva->venta->consumos as $consumo) --}}
                    @if (!is_null($consumo->pagosConsumos->where('id_consumo', $consumo->id)))
                        <a href="{{ route('backoffice.consumo.pdf', $reserva) }}" target="_blank"
                        class="collection-item center-align valign-wrapper left">
                        <i class="material-icons tooltipped" data-position="bottom" data-tooltip="PDF Consumo">local_bar</i></a>
                    @endif
                {{-- @endforeach --}}
            @endif

        @endif

    @endif

</div>