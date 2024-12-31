
 <ul class="collapsible popout">
    <li>
      <div class="collapsible-header pink accent-2 white-text"><i class="material-icons">folder</i>Documentos</div>
      <div class="collapsible-body">
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

                    <a href="{{route('backoffice.cliente.pdf', $reserva)}}" target="_blank" class="collection-item" style="cursor: pointer"><i class='material-icons tooltipped pink-text accent-2' data-position="bottom" data-tooltip="Ver PDF">picture_as_pdf</i></a>

                    {{-- <a class="collection-item" style="cursor: pointer"><i class='material-icons tooltipped' data-position="bottom" data-tooltip="Ver PDF">file_download</i></a> --}}

                </div>
                
            </div>

            @endforeach
        @endif
        
        
</div>
    </li>
    <li>
      <div class="collapsible-header pink accent-2 white-text"><i class="material-icons">av_timer</i>Historial de Reservas</div>
      <div class="collapsible-body">
        @foreach ($cliente->reservas as $reserva)
        <a class="valign-wrapper" href="{{ route('backoffice.reserva.show', $reserva) }}">{{$reserva->fecha_visita}}</a>
        @endforeach
    </div>
    </li>
    <li>
      <div class="collapsible-header pink accent-2 white-text"><i class="material-icons">whatshot</i>Third</div>
      <div class="collapsible-body"><span>Lorem ipsum dolor sit amet.</span></div>
    </li>
  </ul>
  <a href="{{ route('backoffice.reserva.create', $cliente->id) }}" class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right"> <i class="material-icons">add</i> </a>
        