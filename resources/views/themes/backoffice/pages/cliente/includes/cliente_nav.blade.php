<div class="collection">

    <a href="{{route('backoffice.cliente.show', $cliente)}}"  class="collection-item active" ><h5>Reservas:</h5></a>


        @if($cliente->reservas->isEmpty())
            <a class="collection-item center">Este cliente no tiene reservas. </a>
            <a href="{{ route('backoffice.reserva.create', $cliente->id) }}" class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right"> <i class="material-icons">add</i> </a>

        @else
            @foreach($cliente->reservas as $reserva)
            <div class="collection-item center-align valign-wrapper center" style="display: flex; justify-content:space-between;">

                <a href="#modal-reserva{{$reserva->id}}" class="modal-trigger center-align valign-wrapper"
                    @if ($reserva->venta->total_pagar <= 0)
                        style="color:green;"
                    @else
                        style="color:#FF4081;"
                    @endif 
                    data-id="{{ $reserva->id }}" 
                    data-cliente="{{ $cliente->nombre_cliente }}" 
                    data-fecha="{{ $reserva->fecha_visita }}" 
                    data-observacion="{{ $reserva->observacion }}"
                    data-masaje="{{$reserva->cantidad_masajes}}"
                    data-personas="{{$reserva->cantidad_personas}}"
                    >Visita: {{ $reserva->fecha_visita }}</a>

                <div style="display: flex; justify-content:space-between;">

                    <a href="{{route('backoffice.cliente.pdf', $reserva)}}" target="_blank" class="collection-item" style="cursor: pointer"><i class='material-icons tooltipped' data-position="bottom" data-tooltip="Ver PDF">picture_as_pdf</i></a>

                    <a class="collection-item" style="cursor: pointer"><i class='material-icons tooltipped' data-position="bottom" data-tooltip="Ver PDF">file_download</i></a>

                </div>
                
            </div>

            @endforeach
            <a href="{{ route('backoffice.reserva.create', $cliente->id) }}" class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right"> <i class="material-icons">add</i> </a>
        @endif


    
 
 </div>