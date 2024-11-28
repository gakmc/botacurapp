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

        <h5>Venta: @if (is_null($reserva->venta))

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
        data-id="{{ $reserva->venta->id }}" data-abono="{{ $reserva->venta->abono_programa }}"
        data-abonoimg="{{$reserva->venta->imagen_abono ? route('backoffice.reserva.abono.imagen', $reserva->id) : '/images/gallary/no-image.png'}}"
        data-diferencia="{{ $reserva->venta->diferencia_programa }}"
        data-diferenciaimg="{{$reserva->venta->imagen_diferencia ? route('backoffice.reserva.diferencia.imagen', $reserva->id) : '/images/gallary/no-image.png'}}"
        data-descuento="{{$reserva->venta->descuento}}" data-totalpagar="{{$reserva->venta->total_pagar}}"
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

    <a class="collection-item center-align valign-wrapper left dropdown-trigger" href="#" data-target="dropdown1">
        <i class="material-icons tooltipped" data-position="bottom" data-tooltip="PDF">picture_as_pdf</i>
    </a>

    <!-- Dropdown hacia arriba -->
    <ul id="dropdown1" class="dropdown-content">
        <li><a href="{{route('backoffice.venta.pdf', $reserva)}}" target="_blank"><i
                    class="material-icons">remove_red_eye</i>Ver venta</a></li>
        <li><a href="#!"><i class="material-icons">share</i>Compartir</a></li>


        @foreach ($reserva->venta->consumos as $consumo)
        @if ($consumo->pagosConsumos->where('id_consumo', $consumo->id)->isNotEmpty())
        <li>
            <a href="{{ route('backoffice.consumo.pdf', $reserva) }}" target="_blank">
                <i class="material-icons">remove_red_eye</i>Consumo
            </a>
        </li>
        @endif
        @endforeach


    </ul>

    @endif

    {{-- @endforeach --}}
    @endif

</div>