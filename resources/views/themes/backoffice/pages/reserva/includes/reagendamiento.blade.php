<div class="collection">

    <a href="{{--route('backoffice.reagendamiento.show', $reserva)--}}"  class="collection-item active" ><h5>Reagendamiento:</h5></a>


        @if($reserva->reagendamientos->isEmpty())
            <a class="collection-item center">Esta reserva no posee reagendamientos. </a>
            <a href="{{ route('backoffice.reserva.reagendamientos.create', $reserva) }}" class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right tooltipped" data-position="left" data-tooltip="Reagendar"> <i class="material-icons">update</i> </a>

        @else
            @foreach($reserva->reagendamientos as $reagendamiento)
            <a class="collection-item center-align valign-wrapper">{{$reagendamiento->fecha_original}} <span><i class="material-icons">arrow_forward</i></span> {{$reagendamiento->nueva_fecha}}</a>

            @endforeach
            <a href="{{ route('backoffice.reserva.reagendamientos.create', $reserva) }}" class="btn-floating tooltipped activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right"  data-position="left" data-tooltip="Reagendar"> <i class="material-icons">update</i> </a>
        @endif


    
 
 </div>