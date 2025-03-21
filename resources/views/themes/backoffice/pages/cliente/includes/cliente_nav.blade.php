
 <ul class="collapsible popout">
    <li>
      <div class="collapsible-header pink accent-2 white-text"><i class="material-icons">folder</i>Documentos</div>
      <div class="collapsible-body">
        @if($cliente->reservas->isEmpty())
            <a class="collection-item center">Este cliente aun no posee reservas. </a>
            {{-- <a href="{{ route('backoffice.reserva.create', $cliente->id) }}" class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right"> <i class="material-icons">add</i> </a> --}}

        @else
            @foreach($cliente->reservas as $reserva)
            <div class="collection-item center-align valign-wrapper center" style="display: flex; justify-content:space-between;">

                <a href="#modal-reserva" class="modal-trigger center-align valign-wrapper"
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
                    data-programa="{{$reserva->programa->nombre_programa}}"
                    data-abono="{{number_format($reserva->venta->abono_programa,0,'','.')}}"
                    data-tipo_abono="{{$reserva->venta->tipoTransaccionAbono->nombre ?? 'No Registrado'}}"
                    data-diferencia="{{is_null($reserva->venta->diferencia_programa) ? 'Aún no cierra venta' : number_format($reserva->venta->diferencia_programa,0,'','.')}}"
                    data-tipo_diferencia="{{(!is_null($reserva->venta->id_tipo_transaccion_diferencia)) ? $reserva->venta->tipoTransaccionDiferencia->nombre : 'No registrado'}}"
                    @if (isset($reserva->menus))
                    data-menus="{{ $reserva->menus->map(function($menu) {
                        return [
                            'entrada' => optional($menu->productoEntrada)->nombre ?? 'No registra',
                            'fondo' => optional($menu->productoFondo)->nombre ?? 'No registra',
                            'acompanamiento' => optional($menu->productoAcompanamiento)->nombre ?? 'Sin Acompañamiento',
                            'alergias' => optional($menu)->alergias ?? 'No registra',
                            'observacion' => optional($menu)->observacion ?? 'No registra',
                        ];
                    })->toJson() }}"
                    @else
                        data-menus="No registra"
                    @endif

                    data-sauna="{{ $reserva->visitas->pluck('horario_sauna')->filter()->unique()->count() > 0 ? $reserva->visitas->pluck('horario_sauna')->filter()->unique()->join(', ') : 'No registra' }}"

                    data-tinaja="{{ $reserva->visitas->pluck('horario_tinaja')->filter()->unique()->count() > 0 ? $reserva->visitas->pluck('horario_tinaja')->filter()->unique()->join(', ') : 'No registra' }}"

                    data-horariomasajes="{{$masajes->pluck('horario_masaje')->filter()->unique()->isNotEmpty() ? $masajes->pluck('horario_masaje')->filter()->unique()->join(', ') : 'No registra' }}"


                    data-sauna-fin="{{ $reserva->visitas->pluck('horario_sauna')->filter()->unique()->count() > 0 ? $reserva->visitas->pluck('hora_fin_sauna')->filter()->unique()->join(', ') : 'No registra' }}"

                    data-tinaja-fin="{{ $reserva->visitas->pluck('horario_tinaja')->filter()->unique()->count() > 0 ? $reserva->visitas->pluck('hora_fin_tinaja')->filter()->unique()->join(', ') : 'No registra' }}"

                    data-horariomasajes-fin="{{$masajes->pluck('horario_masaje')->filter()->unique()->isNotEmpty() ? $masajes->pluck('hora_final_masaje')->filter()->unique()->join(', ') : 'No registra' }}"


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
        @if($cliente->reservas->isEmpty())

        <a class="collection-item center">Este cliente aun no posee reservas. </a>
        {{-- <a href="{{ route('backoffice.reserva.create', $cliente->id) }}" class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right"> <i class="material-icons">add</i> </a> --}}

        @else

        @foreach ($cliente->reservas as $reserva)
        <a class="valign-wrapper" href="{{ route('backoffice.reserva.show', $reserva) }}">{{$reserva->fecha_visita}}</a>
        @endforeach

        @endif
    </div>
    </li>
  </ul>
  <a href="{{ route('backoffice.reserva.create', $cliente->id) }}" class="btn-floating activator btn-move-up waves-effect waves-light accent-2 z-depth-0 right"> <i class="material-icons">add</i> </a>
        