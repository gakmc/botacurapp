@extends('themes.backoffice.layouts.admin')

@section('title', 'Reserva de '.$reserva->cliente->nombre_cliente)

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.index')}}">Reservas</a></li>
<li>{{$reserva->cliente->nombre_cliente}}</li>
@endsection

@section('dropdown_settings')

  <li><a href="{{ route('backoffice.reserva.edit',$reserva) }}" class="grey-text text-darken-2">Editar Reserva</a></li>
    
  <li><a href="{{ route('backoffice.reserva.visitas.spa', ['reserva' => $reserva, 'visita' => $reserva->visitas->first()]) }}" class="grey-text text-darken-2">Editar Spa</a></li>
    
  @if ($reserva->programa->incluye_masajes || $reserva->incluye_masajes_extra)
    <li><a href="{{ route('backoffice.reserva.masajes', ['reserva' => $reserva]) }}" class="grey-text text-darken-2">Editar Masajes</a></li>
  @endif
  
  @if ($reserva->programa->incluye_almuerzos || $reserva->visitas->last()->incluye_almuerzos_extra)
    <li><a href="{{ route('backoffice.reserva.menus', ['reserva' => $reserva, 'visita' => $reserva->visitas->last()]) }}" class="grey-text text-darken-2">Editar Menú</a></li>
  @endif

@endsection

@section('content')
<div class="section">
  <p class="caption" style="margin-bottom: 0"><strong>Fecha de reserva:</strong> {{ $reserva->fecha_visita }}</p>
  <div class="divider"></div>
  <div id="basic-form" class="section" style="padding-top: 0">
    <div class="row">
      <div class="col s12 m8">
        @if(Auth::user()->has_role(config('app.admin_role')))
        {{-- TABLA CLIENTE --}}
          <div class="card">
            <div class="card-content">
              <span class="card-title activator grey-text text-darken-4">
                <a href="{{route('backoffice.cliente.show',$reserva->cliente)}}">
                  {{$reserva->cliente->nombre_cliente}}  
                </a>
                </span>
              <div class="row">
                <div class="col s12 m6 l4">
                  <p>
                    @if (is_null($reserva->cliente->whatsapp_cliente))
                      <i class="material-icons left">perm_phone_msg</i> No Registra
                    @else
                      <i class="material-icons left">perm_phone_msg</i> <a
                      href="https://api.whatsapp.com/send?phone={{$reserva->cliente->whatsapp_cliente}}"
                      target="_blank">+{{$reserva->cliente->whatsapp_cliente}}</a>
                    @endif

                  </p>
                </div>
                <div class="col s12 m6 l4">

                  <p>

                    @if (is_null($reserva->cliente->instagram_cliente))
                      <i class="material-icons left">perm_identity</i> No Registra
                    @else
                      <i class="material-icons left">perm_identity</i> <a
                      href="https://www.instagram.com/{{$reserva->cliente->instagram_cliente}}"
                      target="_blank">{{$reserva->cliente->instagram_cliente}}</a>
                    @endif


                  </p>
                </div>

                <div class="col s12 m6 l4">
                  <p>

                    @if (is_null($reserva->cliente->correo))
                      <i class="material-icons left">email</i> No Registra
                    @else
                      <i class="material-icons left">email</i> <a href="mailto:{{$reserva->cliente->correo}}"
                      target="_blank">{{$reserva->cliente->correo}}</a>
                    @endif


                  </p>
                </div>

                <div class="col s12 m6 l4">
                  <p>

                    <i class="material-icons left">group</i> Reserva para: <strong>{{$reserva->cantidad_personas}}
                      personas</strong>

                  </p>
                </div>
                <div class="col s12 m6 l4">
                  <p>

                    <i class="material-icons left">verified_user</i> Reserva Generada por: <a
                      href="{{route('backoffice.user.show', $reserva->user_id)}}">{{$reserva->user->name}}</a>

                  </p>
                </div>

              </div>


              @if(Auth::user()->has_role(config('app.admin_role')))
                <div class="card-action">
                  <a href="{{route('backoffice.cliente.edit', $reserva->cliente_id)}}" class="purple-text">Editar datos Cliente</a>
                  {{-- <a href="#" style="color: red" onclick="enviar_formulario()">Eliminar</a> --}}
                </div>
              @endif

            </div>
          </div>

        @elseif (Auth::user()->has_role(config('app.anfitriona_role')))

          @foreach($reserva->visitas as $visita)
            @if ($reserva->menus->isNotEmpty())
              <div class="col s12 m12">
                <ul id="projects-collection" class="collection z-depth-1">
                  <li class="collection-item avatar">
                    <i class="material-icons light-blue darken-4 circle">restaurant_menu</i>
                    <h6 class="collection-header m-0">Menú</h6>
                    <p>Selecciones</p>
                  </li>
                  <table class="responsive-table">
                    <thead>
                      <tr>
                        <th>Menú</th>
                        <th>Entrada</th>
                        <th>Fondo</th>
                        <th>Acompañamiento</th>
                        <th>Observaciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($reserva->menus as $index => $menu)
                        <tr>
                          <td>
                            <strong>Menú {{$index + 1}}:</strong>
                          </td>
                          <td>
                            {{ $menu->productoEntrada->nombre }}
                          </td>
                          <td>
                            {{ $menu->productoFondo->nombre }}
                          </td>
                          <td>
                            @if ($menu->productoAcompanamiento == null)
                              Sin Acompañamiento
                            @else
                              {{ $menu->productoAcompanamiento->nombre }}
                            @endif
                          </td>
                          @if ($menu->observacion == null)
                            <td> No Registra</td>
                          @endif
                          <td style="color: red">{{ $menu->observacion }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </ul>
              </div>
            @endif
          @endforeach

        @endif

      </div>

      @if(Auth::user()->has_role(config('app.admin_role')))
        <div class="col s12 m4">
          @include('themes.backoffice.pages.reserva.includes.reagendamiento')
        </div>
      @else
        <div class="col s12 m4">
          @include('themes.backoffice.pages.reserva.includes.consumo')
        </div>
      @endif
    </div>

    <div class="row">
      <div class="col s12 m8">
        {{-- TABLA PROGRAMA --}}
        <div id="work-collections">
          <div class="row">
            <div class="col s12 m12 l5">
              <ul id="projects-collection" class="collection z-depth-1">
                <li class="collection-item avatar">
                  <i class="material-icons cyan circle">card_travel</i>
                  <h6 class="collection-header m-0">Programa {{$reserva->programa->nombre_programa}}</h6>
                  <p>Servicios incluidos</p>
                </li>
                @foreach ($reserva->programa->servicios as $servicio)
                  <li class="collection-item">
                    <div class="row">
                      <div class="col s9">
                        <p class="collections-title">{{$servicio->nombre_servicio}}</p>
                        <p class="collections-content">{{$servicio->duracion}} minutos</p>
                      </div>
                      {{-- <div class="col s3">
                        <span class="task-cat cyan accent-2">Pendiente</span>
                      </div> --}}
                    </div>
                  </li>
                @endforeach
              </ul>
            </div>

            {{-- TABLA VISITA --}}

            <div class="col s12 m12 l7">
              <ul id="issues-collection" class="collection z-depth-1">
                <li class="collection-item avatar">
                  <i class="material-icons green accent-2 circle">spa</i>
                  <h6 class="collection-header m-0">Visita <a class="btn-floating btn waves-effect waves-light right tooltipped" data-position="bottom" data-tooltip="Cambiar Ubicación" href="{{route('backoffice.visita.edit_ubicacion',['visitum'=>$reserva->visitas->first()])}}"><i class="material-icons green accent-2">transfer_within_a_station</i></a></h6>
                  <p>{{$reserva->visitas->first()->ubicacion->nombre ?? 'Ubicacion no registrada'}}</p>
                  @if ($reserva->visitas->isEmpty())
                      <h6>Aún no se registra la visita para esta reserva</h6>
                  @else
                      @php
                        $mostrados = [];
                      @endphp
                        <ul class="collapsible expandable">
                          <li>
                            <div class="collapsible-header"><i class="material-icons">filter_drama</i>Horarios Sauna</div>
                            <div class="collapsible-body">
                            @foreach ($visitas as $indexS=>$visita)
                              <div class="row">
                                <div class="col s7">
                                  <p class="collections-title">Sauna: <strong id="horario-sauna" class="horario-sauna"
                                      data-fecha="{{ $reserva->fecha_visita }}" data-inicio="{{ $visita->horario_sauna }}"
                                      data-fin="{{ $visita->hora_fin_sauna }}">{{ $visita->horario_sauna }}</strong></p>
                                  <p class="collections-content">Hora Fin: <strong name="sauna" id="sauna" data-sauna="duracion-sauna">{{
                                      $visita->hora_fin_sauna }}</strong></p>
                                </div>
                                <div class="col s3">
                                  <span class="task-cat" id="task-cat-sauna-{{$indexS}}">Pendiente</span>
                                </div>
                                <div class="col s3">
                                  <div class="progress">
                                    <div class="determinate" id="progress-sauna-{{$indexS}}" style="width: 0%;"></div>
                                  </div>
                                </div>
                              </div>
                            @endforeach
                            </div>
                          </li>
                          <li>
                            <div class="collapsible-header"><i class="material-icons">hot_tub</i>Horarios Tinaja</div>
                            <div class="collapsible-body">
                            @foreach ($visitas as $indexT=>$visita)
                              <div class="row">
                                <div class="col s7">
                                  <p class="collections-title">Tinaja: <strong id="horario-tinaja" class="horario-tinaja"
                                      data-fecha="{{ $reserva->fecha_visita }}" data-inicio="{{ $visita->horario_tinaja }}"
                                      data-fin="{{ $visita->hora_fin_tinaja }}">{{ $visita->horario_tinaja }}</strong></p>
                                  <p class="collections-content">Hora Fin: <strong name="tinaja" id="tinaja" data-tinaja="duracion-tinaja">{{
                                      $visita->hora_fin_tinaja }}</strong></p>
                                </div>
                                <div class="col s3">
                                  <span class="task-cat cyan" id="task-cat-tinaja-{{$indexT}}">Pendiente</span>
                                </div>
                                <div class="col s3">
                                  <div class="progress">
                                    <div class="determinate" id="progress-tinaja-{{$indexT}}" style="width: 0%;"></div>
                                  </div>
                                </div>
                              </div>
                            @endforeach
                            </div>
                          </li>

                          @if (isset($masajes))
                          <li>
                            <div class="collapsible-header"><i class="material-icons">airline_seat_flat</i>Horarios Masaje</div>
                            <div class="collapsible-body">
                              @foreach ($masajes as $indexM => $masaje)
                                  @if($reserva->programa->incluye_masajes)
                                      <div class="row">
                                          <div class="col s7">
                                            <p class="collections-content">
                                                Lugar: 
                                                <strong name="lugar" id="lugar">
                                                    {{ $masaje->lugarMasaje->nombre ?? 'No Registra' }}
                                                </strong>
                                            </p>
                                            <br>
                                              <p class="collections-title">
                                                  Masaje: 
                                                  <strong id="horario-masaje-{{$indexM}}" class="horario-masaje"
                                                      data-fecha="{{ $reserva->fecha_visita }}" 
                                                      data-inicio="{{ $masaje->horario_masaje }}"
                                                      data-fin="{{ $masaje->hora_fin_masaje }}">
                                                      {{ $masaje->horario_masaje }}
                                                  </strong>
                                              </p>
                                              <p class="collections-content">
                                                  Hora Fin: 
                                                  <strong name="masaje" id="masaje" data-masaje="duracion-masaje">
                                                      {{ $masaje->hora_fin_masaje }}
                                                  </strong>
                                              </p>
                                              <br>
                                          </div>
                                          <div class="col s3">
                                            <span class="task-cat cyan" id="task-cat-masaje-{{$indexM}}">Pendiente</span>
                                          </div>
                                          <div class="col s3">
                                            <div class="progress">
                                              <div class="determinate" id="progress-masaje-{{$indexM}}" style="width: 0%;"></div>
                                            </div>
                                          </div>
                                      </div>
                                  @endif
                            
                                  @if(!$reserva->programa->incluye_masajes && $masaje->horario_masaje)



                                      {{-- <div class="row">
                                        <div class="col s7">
                                          <p class="collections-content">
                                              Lugar: 
                                              <strong name="lugar" id="lugar">
                                                  {{ $masaje->lugarMasaje->nombre ?? 'No Registra' }}
                                              </strong>
                                          </p>
                                          <br>
                                            <p class="collections-title">
                                                Masaje Extra: 
                                                <strong id="horario-masaje-{{$indexM}}" class="horario-masaje"
                                                    data-fecha="{{ $reserva->fecha_visita }}" 
                                                    data-inicio="{{ $masaje->horario_masaje ?? "00:00"}}"
                                                    data-fin="{{ $masaje->hora_fin_masaje ?? "00:30"}}">
                                                    {{ $masaje->horario_masaje }}
                                                </strong>
                                            </p>
                                            <p class="collections-content">
                                                Hora Fin: 
                                                <strong name="masaje" id="masaje" data-masaje="duracion-masaje">
                                                    {{ $masaje->hora_fin_masaje }}
                                                </strong>
                                            </p>
                                            <br>
                                        </div>
                                        <div class="col s3">
                                          <span class="task-cat cyan" id="task-cat-masaje-{{$indexM}}">Pendiente</span>
                                        </div>
                                        <div class="col s3">
                                          <div class="progress">
                                            <div class="determinate" id="progress-masaje-{{$indexM}}" style="width: 0%;"></div>
                                          </div>
                                        </div>
                                      </div> --}}

                                      <div class="row">
                                        <div class="col s7">
                                          <p class="collections-title">Masaje Extra: <strong id="horario-masaje" class="horario-masaje"
                                              data-fecha="{{ $reserva->fecha_visita }}" data-inicio="{{ $masaje->horario_masaje }}"
                                              data-fin="{{ $masaje->hora_fin_masaje_extra }}">{{ $masaje->horario_masaje }}</strong></p>
                                          <p class="collections-content">Hora Fin: <strong name="masaje" id="masaje" data-masaje="duracion-masaje">{{
                                              $masaje->hora_fin_masaje_extra }}</strong></p>
                                        </div>
                                        <div class="col s3">
                                          <span class="task-cat" id="task-cat-masaje-{{$indexM}}">Pendiente</span>
                                        </div>
                                        <div class="col s3">
                                          <div class="progress">
                                            <div class="determinate" id="progress-masaje-{{$indexM}}" style="width: 0%;"></div>
                                          </div>
                                        </div>
                                      </div>

                                  @endif
                              @endforeach
                            
                              </div>
                          </li>


                          @else

                            <div class="collapsible-header"><i class="material-icons">airline_seat_flat</i>Horarios Masaje - <strong class="pink-text accent-2"> No registra</strong> </div>
                            
                          @endif
                        </ul>
      


                  @endif




              </ul>
            </div>


            {{-- Menus --}}
            @if(Auth::user()->has_role(config('app.admin_role')))

              @if ($reserva->menus->isNotEmpty())
                <div class="col s12 m12">
                  <ul id="projects-collection" class="collection z-depth-1">
                    <li class="collection-item avatar">
                      <i class="material-icons light-blue darken-4 circle">restaurant_menu</i>
                      <h6 class="collection-header m-0">Menú</h6>
                      <p>Selecciones</p>
                    </li>


                    <table class="responsive-table">
                      <thead>
                        <tr>
                          <th>Menú</th>
                          <th>Entrada</th>
                          <th>Fondo</th>
                          <th>Acompañamiento</th>
                          <th>Alérgias</th>
                          <th>Observaciones</th>
                        </tr>
                      </thead>
                      <tbody>

                        @foreach($reserva->menus as $index => $menu)
                          <tr>
                            <td>
                              <strong>Menú {{$index + 1}}:</strong>
                            </td>
                            <td>
                              {{ $menu->productoEntrada->nombre ?? 'Sin Entrada' }}
                            </td>
                            <td>

                              {{ $menu->productoFondo->nombre ?? 'No registra' }}
                            </td>
                            <td>
                              @if ($menu->productoAcompanamiento == null)
                              Sin Acompañamiento
                              @else

                              {{ $menu->productoAcompanamiento->nombre }}
                              @endif
                            </td>

                            @if ($menu->alergias == null)
                              <td> No Registra</td>
                            @else
                              <td style="color: red">{{ $menu->alergias }}</td>
                            @endif

                            @if ($menu->observacion == null)
                              <td> No Registra</td>
                            @else
                              <td style="color: red">{{ $menu->observacion }}</td>
                            @endif

                          </tr>
                        @endforeach



                      </tbody>
                    </table>


                  </ul>
                </div>

              @endif


            @endif


          </div>
        </div>



      </div>

      @if(Auth::user()->has_role(config('app.admin_role')))
      <div class="col s12 m4">
        @include('themes.backoffice.pages.reserva.includes.venta')
      </div>

      <div class="col s12 m4">
        @include('themes.backoffice.pages.reserva.includes.consumo')
      </div>

      @include('themes.backoffice.pages.reserva.includes.modal_venta')
      @endif
    </div>

  </div>
</div>
</div>

<form method="post" action="{{route('backoffice.reserva.destroy', $reserva) }} " name="delete_form">
  {{csrf_field()}}
  {{method_field('DELETE')}}
</form>
@endsection

@section('foot')
{{-- MATERIALIZE Init --}}
<script>

  $(document).ready(function(){
      $('.tooltipped').tooltip();
      
      $('.collapsible').collapsible({
        accordion:false
      });

      $('.modal').modal();


  });

</script>

{{-- Propiedades Materialize --}}
{{-- <script>
  document.addEventListener('DOMContentLoaded', function () {
    var elems = document.querySelectorAll('.dropdown-trigger');
    M.Dropdown.init(elems, {
      alignment: 'right', // Alinea a la derecha
      constrainWidth: false, // No limita el ancho del dropdown
      coverTrigger: true, // No cubre el botón
      hover: false, // Solo abre al hacer clic
      inDuration: 300, // Duración de la animación al abrir
      outDuration: 200, // Duración de la animación al cerrar
      belowOrigin: true // Aparece hacia arriba
    });
  });
</script> --}}

{{-- Pasar Data a Modal --}}
<script>
  function formatCLP(number) {
      return isNaN(number) ? '$0' : '$' + parseInt(number, 10).toLocaleString('es-CL');
  }

  $(document).ready(function(){
    $('.modal-trigger').on('click', function(){
          // Obtener los datos del cliente y la reserva seleccionada
          var abono = $(this).data('abono') || 0;
          var abonoImg = $(this).data('abonoimg');
          var diferencia = $(this).data('diferencia') || 0;
          var diferenciaImg = $(this).data('diferenciaimg');
          var descuento = $(this).data('descuento');
          var totalPagar = $(this).data('totalpagar');
          var tipoAbono = $(this).data('tipoabono');
          var tipoDiferencia = $(this).data('tipodiferencia');
          var consumos = $(this).data('consumo');
          var pagoconsumo = $(this).data('pagoimg') || null;
          

          if (pagoconsumo === null) {
              $('#consumoSeparado').attr('hidden', true);
              $('#pConsumoSeparado').attr('hidden', true);
            } else {
              $('#consumoSeparado').removeAttr('hidden'); 
            }
          

          // Insertar los datos en los elementos del modal
          $('#modalAbono').text(formatCLP(abono));
          $('#modalDiferencia').text(formatCLP(diferencia));
          
          $('#linkAbono').attr('href',abonoImg);
          $('#linkDiferencia').attr('href',diferenciaImg);
          $('#linkConsumo').attr('href',pagoconsumo);
          $('#modalAbonoImg').attr('src',abonoImg);
          $('#modalDiferenciaImg').attr('src',diferenciaImg);
          $('#modalConsumoImg').attr('src',pagoconsumo);
          
              // Validar si el descuento es nulo
              if (descuento == null || descuento == '') {
                $('#modalDescuento').text(formatCLP(0));
              } else {
                $('#modalDescuento').text(formatCLP(descuento));
              }



          $('#modalTotalPagar').text(formatCLP(totalPagar));
          $('#modalTipoAbono').text(tipoAbono);
          $('#modalTipoDiferencia').text(tipoDiferencia);
          
          
          // Limpiar el contenido anterior de consumos en el modal
          $('#modalConsumo').empty();

          // Crear la tabla para los consumos
          var subtotalConsumo=0;
          var totalConsumo = 0;
          var subtotalServicio=0;

          var tablaConsumos = '<table class="highlight responsive-table centered">';

          tablaConsumos += '<thead><tr><th>Producto</th><th>Valor</th><th>Cantidad</th><th>SubTotal</th></tr></thead>';
          tablaConsumos += '<tbody>';

          // Iterar sobre los consumos y agregar filas a la tabla
          if (Array.isArray(consumos) && consumos.length > 0) {
              consumos.forEach(function(consumo, index) {
                  if (Array.isArray(consumo.detalles_consumos) && consumo.detalles_consumos.length > 0) {
                      consumo.detalles_consumos.forEach(function(detalle, detalleIndex) {
                          tablaConsumos += '<tr>';
                          tablaConsumos += '<td>' + detalle.producto.nombre + '</td>';  // Cambia si tienes un nombre específico del producto
                          tablaConsumos += '<td>' + formatCLP(detalle.producto.valor) + '</td>';
                          tablaConsumos += '<td>X' + detalle.cantidad_producto + '</td>';
                          tablaConsumos += '<td>' + formatCLP(detalle.subtotal) + '</td>';
                          subtotalConsumo += detalle.subtotal;
                          totalConsumo += detalle.subtotal*1.1;
                          tablaConsumos += '</tr>';
                      });
                  }
              });
              tablaConsumos += '<tr>';
              tablaConsumos += '<td>' + '</td>'; 
              tablaConsumos += '<td>' + '</td>'; 
              tablaConsumos += '<td>' + '</td>'; 
              tablaConsumos += '<td class="right">' + '<strong>SubTotal: '+formatCLP(subtotalConsumo)+'</strong>' + '</td>'; 
              tablaConsumos += '</tr>';

              tablaConsumos += '<tr>';
              tablaConsumos += '<td>' + '</td>'; 
              tablaConsumos += '<td>' + '</td>'; 
              tablaConsumos += '<td>' + '</td>'; 
              tablaConsumos += '<td class="right">' + '<strong>Propinas: '+formatCLP(subtotalConsumo*0.1)+'</strong>' + '</td>'; 
              tablaConsumos += '</tr>';
              
              tablaConsumos += '<tr>';
              tablaConsumos += '<td>' + '</td>'; 
              tablaConsumos += '<td>' + '</td>'; 
              tablaConsumos += '<td>' + '</td>'; 
              tablaConsumos += '<td class="right">' + '<strong>Total: '+formatCLP(Math.trunc(totalConsumo))+'</strong>' + '</td>'; 
              tablaConsumos += '</tr>';

          } else {
              tablaConsumos += '<tr><td colspan="4">No hay consumos registrados.</td></tr>';
          }

          tablaConsumos += '</tbody></table>';

          // Añadir la tabla al modal
          $('#modalConsumo').append(tablaConsumos);


          // Limpiar el contenido anterior de consumos en el modal
          $('#modalServicio').empty();

          var tablaServicios = '<table class="highlight responsive-table centered">';
          tablaServicios += '<thead><tr><th>Servicio</th><th>Valor</th><th>Cantidad</th><th>SubTotal</th></tr></thead>';
          tablaServicios += '<tbody>';

                // Iterar sobre los consumos y agregar filas a la tabla
                if (Array.isArray(consumos) && consumos.length > 0) {
              consumos.forEach(function(consumo, index) {
                  if (Array.isArray(consumo.detalle_servicios_extra) && consumo.detalle_servicios_extra.length > 0) {
                      consumo.detalle_servicios_extra.forEach(function(detalle, detalleIndex) {
                          tablaServicios += '<tr>';
                          tablaServicios += '<td>' + detalle.servicio.nombre_servicio + '</td>';
                          tablaServicios += '<td>' + formatCLP(detalle.servicio.valor_servicio) + '</td>';
                          tablaServicios += '<td>' +'X'+ detalle.cantidad_servicio + '</td>';
                          tablaServicios += '<td>' + formatCLP(detalle.subtotal) + '</td>';
                          subtotalServicio += detalle.subtotal;
                          tablaServicios += '</tr>';
                      });
                  }
              });
              tablaServicios += '<tr>';
              tablaServicios += '<td>' + '</td>'; 
              tablaServicios += '<td>' + '</td>'; 
              tablaServicios += '<td>' + '</td>'; 
              tablaServicios += '<td>' + '<strong>Total:'+formatCLP(subtotalServicio)+'</strong>' + '</td>'; 
              tablaServicios += '</tr>';

          } else {
              tablaServicios += '<tr><td colspan="4">No hay servicios registrados.</td></tr>';
          }

          tablaServicios += '</tbody></table>';

          // Añadir la tabla al modal
          $('#modalServicio').append(tablaServicios);
          
          
          // Limpiar el contenido anterior de consumos en el modal
          $('#modalResumen').empty();
          
          var SubTotalPagar = subtotalConsumo+subtotalServicio+totalPagar;
          var TotalPagarCP = Math.trunc(totalConsumo)+subtotalServicio+totalPagar;

          var tablaResumen = '<table class="highlight responsive-table centered">';
            
            tablaResumen += '<thead><tr><th>Total Consumo</th><th>Total Servicios</th><th>Diferencia</th><th>Total</th></tr></thead>';
            tablaResumen += '<tbody>';

            tablaResumen += '<tr>';
            tablaResumen += '<td>' + formatCLP(Math.trunc(totalConsumo)) + '</td>'; 
            tablaResumen += '<td>' + formatCLP(subtotalServicio) + '</td>'; 
            tablaResumen += '<td>' + formatCLP(totalPagar) + '</td>'; 
            tablaResumen += '<td>' + '<strong> '+formatCLP(TotalPagarCP)+'</strong>' + '</td>'; 
            tablaResumen += '</tr>';

            tablaResumen += '</tbody></table>';
              
              // Añadir la tabla al modal
              $('#modalResumen').append(tablaResumen);

      // Abrir el modal
      var modal = M.Modal.getInstance($('#modalVenta'));
      modal.open();
    });
  });
</script>

{{-- Boton para eliminar --}}
<script>
  function enviar_formulario() {
     Swal.fire({
         title: "¿Deseas eliminar esta reserva?",
         text: "Esta acción no se puede deshacer",
         type: "warning",
         showCancelButton: true,
         confirmButtonText: "Si, continuar",
         cancelButtonText: "No, cancelar",
         closeOnCancel: false,
         closeOnConfirm: true
     }).then((result)=> {
         if(result.value){
             document.delete_form.submit();
         }else{
             Swal.fire(
                 'Operación Cancelada',
                 'Registro no eliminado',
                 'error'
             )
         }
     });
  }
</script>

{{-- Barra de progreso y estado --}}
<script>
  document.addEventListener('DOMContentLoaded', function() {
      function convertirFecha(fechaStr) {
          const partes = fechaStr.split('-'); //convertir la fecha recibida para formatearla
          return `${partes[2]}-${partes[1]}-${partes[0]}`; // Convertir a yyyy-mm-dd
      }

      function calcularProgreso(horaInicioStr, horaFinStr, fechaStr) {
          const fechaHoy = new Date();
          const fechaConvertida = convertirFecha(fechaStr);
          const horaInicio = new Date(`${fechaConvertida} ${horaInicioStr}`);
          const horaFin = new Date(`${fechaConvertida} ${horaFinStr}`);

          if (fechaHoy > horaFin) return 100;
          if (fechaHoy < horaInicio) return 0;

          const totalMilisegundos = horaFin.getTime() - horaInicio.getTime();
          const milisegundosTranscurridos = fechaHoy.getTime() - horaInicio.getTime();

          return (milisegundosTranscurridos / totalMilisegundos) * 100;
      }
      

      function actualizarProgreso(servicio) {
          document.querySelectorAll(`.horario-${servicio}`).forEach((element, index) => {
              const fecha = element.getAttribute('data-fecha');
              const horaInicio = element.getAttribute('data-inicio');
              const horaFin = element.getAttribute('data-fin');

              const progreso = calcularProgreso(horaInicio, horaFin, fecha);

              const progressBar = document.getElementById(`progress-${servicio}-${index}`);
              const taskCat = document.getElementById(`task-cat-${servicio}-${index}`);
              

              if (progressBar) {
                  progressBar.style.width = progreso + '%';
              }

              if (taskCat) {
                  if (progreso === 0) {
                      taskCat.innerText = 'Pendiente';
                      taskCat.className = 'task-cat cyan';
                  } else if (progreso > 0 && progreso < 100) {
                      taskCat.innerText = 'En Proceso';
                      taskCat.className = 'task-cat deep-orange';
                  } else if (progreso === 100) {
                      taskCat.innerText = 'Completado';
                      taskCat.className = 'task-cat green';
                  }
              }
          });
      }

      // Actualizar todas las barras cada segundo
      setInterval(function() {
          actualizarProgreso('sauna');
          actualizarProgreso('tinaja');
          actualizarProgreso('masaje');
        }, 1000);


    });
    
    
</script>

{{-- Alertas --}}
<script>
  @if(session('info'))
    Swal.fire({
        toast: true,
        position: '',
        icon: 'info',
        title: '{{ session('info') }}',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
          didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
          }
    });
  @endif

  @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: '{{ session('success') }}',
            showConfirmButton: true,
            confirmButtonText: `Confirmar`,
            timer: 5000,
        });
  @endif
</script>

@endsection