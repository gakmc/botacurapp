<div class="collection">

    
    <a href="" class="collection-item active @if (is_null($reserva->venta))
        ''
    @else
        @if ($reserva->venta->total_pagar === 0)
            green
        @else
            ' '
        @endif
    @endif
    ">

        <h5 >Venta: @if (is_null($reserva->venta))
            
            @else
            @if ($reserva->venta->total_pagar === 0)
            Pagado
            @endif
            @endif </h5>
        </a>


    @if(is_null($reserva->venta))
    <a class="collection-item center">Esta reserva no posee venta. </a>
    {{-- <a href="{{ route('backoffice.reserva.venta.create', $reserva) }}"
        class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right">
        <i class="material-icons">add</i>
    </a> --}}
    @else
    {{-- @foreach($reserva->venta as $venta) --}}
    <a class="collection-item center-align valign-wrapper left">
        Abono: {{$reserva->venta->abono_programa}}
    </a>
    <a class="collection-item center-align valign-wrapper left">
        Diferencia: @if (is_null($reserva->venta->diferencia_programa))
        Debe Realizar pago
        @else
        {{$reserva->venta->diferencia_programa}}
        @endif
    </a>
    <a href="#modal-venta{{$reserva->venta->id}}" class="modal-trigger collection-item center-align valign-wrapper left"
        data-id="{{ $reserva->venta->id }}" 
        data-abono="{{ $reserva->venta->abono_programa }}"
        data-abonoimg="{{$reserva->venta->imagen_abono ? route('backoffice.reserva.abono.imagen', $reserva->id) : '/images/gallary/no-image.png'}}" data-diferencia="{{ $reserva->venta->diferencia_programa }}"
        data-diferenciaimg="{{$reserva->venta->imagen_diferencia ? route('backoffice.reserva.diferencia.imagen', $reserva->id) : '/images/gallary/no-image.png'}}" data-descuento="{{$reserva->venta->descuento}}"
        data-totalpagar="{{$reserva->venta->total_pagar}}" data-tipoabono="{{$reserva->venta->tipoTransaccionAbono->nombre ?? 'No registra'}}"
        data-tipodiferencia="{{$reserva->venta->tipoTransaccionDiferencia->nombre ?? 'No registra'}}"
        data-consumo="{{$reserva->venta->consumos}}"

        >
        <i class='material-icons tooltipped' data-position="bottom" data-tooltip="Ver Venta">remove_red_eye</i>
    </a>
    <a href="{{route('backoffice.reserva.venta.cerrar', ['reserva'=>$reserva, 'ventum'=>$reserva->venta]) }}" class="collection-item center-align valign-wrapper left">
        <i class='material-icons tooltipped' data-position="bottom" data-tooltip="Cerrar Venta">attach_money</i>
    </a>
    {{-- @endforeach --}}
    @endif

</div>
