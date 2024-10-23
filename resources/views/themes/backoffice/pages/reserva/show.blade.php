@extends('themes.backoffice.layouts.admin')

@section('title', 'Reserva de '.$reserva->cliente->nombre_cliente)

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.index')}}">Reservas</a></li>
<li>{{$reserva->cliente->nombre_cliente}}</li>
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{ route('backoffice.reserva.visitas.create',$reserva) }}" class="grey-text text-darken-2">Generar
    Visita</a></li> --}}
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
            <span class="card-title activator grey-text text-darken-4">{{$reserva->cliente->nombre_cliente}}</span>
            <div class="row">
              <div class="col s12 m6 l4">
                <p>
                  @if (is_null($reserva->cliente->whatsapp_cliente))
                  <i class="material-icons">perm_phone_msg</i> No Registra
                  @else
                  <i class="material-icons">perm_phone_msg</i> <a
                    href="https://api.whatsapp.com/send?phone={{$reserva->cliente->whatsapp_cliente}}"
                    target="_blank">+{{$reserva->cliente->whatsapp_cliente}}</a>
                  @endif

                </p>
              </div>
              <div class="col s12 m6 l4">

                <p>

                  @if (is_null($reserva->cliente->instagram_cliente))
                  <i class="material-icons">perm_identity</i> No Registra
                  @else
                  <i class="material-icons">perm_identity</i> <a
                    href="https://www.instagram.com/{{$reserva->cliente->instagram_cliente}}"
                    target="_blank">{{$reserva->cliente->instagram_cliente}}</a>
                  @endif


                </p>
              </div>

              <div class="col s12 m6 l4">
                <p>

                  @if (is_null($reserva->cliente->correo))
                  <i class="material-icons">email</i> No Registra
                  @else
                  <i class="material-icons">email</i> <a href="mailto:{{$reserva->cliente->correo}}"
                    target="_blank">{{$reserva->cliente->correo}}</a>
                  @endif


                </p>
              </div>

              <div class="col s12 m6 l4">
                <p>

                  <i class="material-icons">group</i> Reserva para: <strong>{{$reserva->cantidad_personas}}
                    personas</strong>

                </p>
              </div>
              <div class="col s12 m6 l4">
                <p>

                  <i class="material-icons">verified_user</i> Reserva Generada por: <a
                    href="{{route('backoffice.user.show', $reserva->user_id)}}">{{$reserva->user->name}}</a>

                </p>
              </div>

            </div>


            @if(Auth::user()->has_role(config('app.admin_role')))
            <div class="card-action">
              <a href="{{route('backoffice.cliente.edit', $reserva->cliente_id)}}" class="purple-text">Editar</a>
              {{-- <a href="#" style="color: red" onclick="enviar_formulario()">Eliminar</a> --}}
            </div>
            @endif

          </div>
        </div>

        @elseif (Auth::user()->has_role(config('app.anfitriona_role')))

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

                @foreach($reserva->visitas as $visita)
                @foreach($visita->menus as $index => $menu)
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

                    {{ $menu->productoAcompanamiento->nombre }}
                  </td>

                  @if ($menu->observacion == null)
                  <td> No Registra</td>
                  @endif
                  <td style="color: red">{{ $menu->observacion }}</td>

                </tr>
                @endforeach
                @endforeach


              </tbody>
            </table>


          </ul>
        </div>

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
                    <div class="col s3">
                      {{-- <span class="task-cat cyan accent-2">Pendiente</span> --}}
                    </div>
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
                  <h6 class="collection-header m-0">Visita</h6>
                  @if ($reserva->visitas->isEmpty())

                  <h6>Aún no se registra la visita para esta reserva</h6>
                  @else
                  @foreach ($reserva->visitas as $visita)
                  <p>@if (is_null($visita->id_lugar_masaje))
                    No Registra Masajes
                    @else
                    {{$visita->lugarMasaje->nombre}}
                    @endif - {{$visita->ubicacion->nombre}}</p>
                </li>


                @if($reserva->programa->servicios->contains('nombre_servicio', 'Sauna'))
                <li class="collection-item">
                  <div class="row">
                    <div class="col s7">
                      <p class="collections-title">Sauna: <strong id="horario-sauna"
                          data-fecha="{{ $reserva->fecha_visita }}" data-inicio="{{ $visita->horario_sauna }}"
                          data-fin="{{ $visita->hora_fin_sauna }}">{{ $visita->horario_sauna }}</strong></p>
                      <p class="collections-content">Hora Fin: <strong name="sauna" id="sauna"
                          data-sauna="duracion-sauna">{{ $visita->hora_fin_sauna }}</strong></p>
                    </div>
                    <div class="col s3">
                      <span class="task-cat" id="task-cat-sauna">Pendiente</span>
                    </div>
                    <div class="col s3">
                      <div class="progress">
                        <div class="determinate" id="progress-sauna" style="width: 0%;"></div>
                      </div>
                    </div>
                  </div>
                </li>
                @endif


                @if($reserva->programa->servicios->contains('nombre_servicio', 'Tinaja'))
                <li class="collection-item">
                  <div class="row">
                    <div class="col s7">
                      <p class="collections-title">Tinaja: <strong id="horario-tinaja"
                          data-fecha="{{ $reserva->fecha_visita }}" data-inicio="{{ $visita->horario_tinaja }}"
                          data-fin="{{ $visita->hora_fin_tinaja }}">{{ $visita->horario_tinaja }}</strong></p>
                      <p class="collections-content">Hora Fin: <strong name="tinaja" id="tinaja"
                          data-tinaja="duracion-tinaja">{{ $visita->hora_fin_tinaja }}</strong></p>
                    </div>
                    <div class="col s3">
                      <span class="task-cat" id="task-cat-tinaja">Pendiente</span>
                    </div>
                    <div class="col s3">
                      <div class="progress">
                        <div class="determinate" id="progress-tinaja" style="width: 0%;"></div>
                      </div>
                    </div>
                  </div>
                </li>
                @endif

                @if($reserva->programa->servicios->contains('nombre_servicio', 'Masaje'))
                <li class="collection-item">
                  <div class="row">
                    <div class="col s7">
                      <p class="collections-title">Masaje: <strong id="horario-masaje"
                          data-fecha="{{ $reserva->fecha_visita }}" data-inicio="{{ $visita->horario_masaje }}"
                          data-fin="{{ $visita->hora_fin_masaje }}">{{ $visita->horario_masaje }}</strong></p>
                      <p class="collections-content">Hora Fin: <strong name="masaje" id="masaje"
                          data-masaje="duracion-masaje">{{ $visita->hora_fin_masaje }}</strong></p>
                    </div>
                    <div class="col s3">
                      <span class="task-cat" id="task-cat-masaje">Pendiente</span>
                    </div>
                    <div class="col s3">
                      <div class="progress">
                        <div class="determinate" id="progress-masaje" style="width: 0%;"></div>
                      </div>
                    </div>
                  </div>
                </li>
                @endif


                @if(!$reserva->programa->servicios->contains('nombre_servicio', 'Masaje') && $visita->horario_masaje)
                <li class="collection-item">
                  <div class="row">
                    <div class="col s7">
                      <p class="collections-title">Masaje extra: <strong id="horario-masaje"
                          data-fecha="{{ $reserva->fecha_visita }}" data-inicio="{{ $visita->horario_masaje }}"
                          data-fin="{{ $visita->hora_fin_masaje_extra }}">{{ $visita->horario_masaje }}</strong></p>
                      <p class="collections-content">Hora Fin: <strong name="masaje" id="masaje"
                          data-masaje="duracion-masaje">{{ $visita->hora_fin_masaje_extra }}</strong></p>
                    </div>
                    <div class="col s3">
                      <span class="task-cat" id="task-cat-masaje">Pendiente</span>
                    </div>
                    <div class="col s3">
                      <div class="progress">
                        <div class="determinate" id="progress-masaje" style="width: 0%;"></div>
                      </div>
                    </div>
                  </div>
                </li>
                @endif


                @endforeach
                @endif




              </ul>
            </div>


            {{-- Menus --}}
            @if(Auth::user()->has_role(config('app.admin_role')))
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

                    @foreach($reserva->visitas as $visita)
                    @foreach($visita->menus as $index => $menu)
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

                        {{ $menu->productoAcompanamiento->nombre }}
                      </td>

                      @if ($menu->observacion == null)
                      <td> No Registra</td>
                      @endif
                      <td style="color: red">{{ $menu->observacion }}</td>

                    </tr>
                    @endforeach
                    @endforeach


                  </tbody>
                </table>


              </ul>
            </div>
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
<script>
  function enviar_formulario()
 {
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


<script>
  document.addEventListener('DOMContentLoaded', function() {
    function convertirFecha(fechaStr) {
        const partes = fechaStr.split('-'); //convertir la fecha recibida para formatearla
        return `${partes[2]}-${partes[1]}-${partes[0]}`; // Convertir a yyyy-mm-dd
    }

    function calcularProgreso(horaInicioStr, horaFinStr, fechaStr) {
        const fechaHoy = new Date(); //ingresar fecha actual en una constante
        const fechaConvertida = convertirFecha(fechaStr); //Fecha recibida desde data-fecha con la fecha de visita en la reserva
        const horaInicio = new Date(`${fechaConvertida} ${horaInicioStr}`); //se genera una constante con el formato esperado por JS (yyyy-mm-dd) de la fecha y hora Inicio
        const horaFin = new Date(`${fechaConvertida} ${horaFinStr}`); //se genera una constante con el formato esperado por JS (yyyy-mm-dd) de la fecha y hora Fin

        if (fechaHoy > horaFin) {
            return 100; // Si la fecha actual es mayor a la horaFin el estado sera Completado (ya sea por la hora o fecha)
        }

        if (fechaHoy < horaInicio) {
            return 0; // Si la fecha actual es menor a la HoraInicio el estado sera Pendiente (puesto que aun no es la fecha ni la hora)
        }

        const totalMilisegundos = horaFin.getTime() - horaInicio.getTime(); // Se obtienen los milisegundos de la hora de inicio con la duracion del servicio.
        
        const milisegundosTranscurridos = fechaHoy.getTime() - horaInicio.getTime();// Se obtienen los milisegundos que han pasado

        
        return (milisegundosTranscurridos / totalMilisegundos) * 100; // Retorna el tiempo en % hasta 100
    }

    function actualizarProgreso(idHorario, idProgressBar, idTaskCat) {
        const horarioElement = document.getElementById(idHorario); //captura el ID de Strong en la constante
        if (!horarioElement) return; // Si no existe ID no realizara ninguna accion

        const fecha = horarioElement.getAttribute('data-fecha'); //obtiene el valor de data-fecha del elemento Strong
        const horaInicio = horarioElement.getAttribute('data-inicio');//obtiene el valor de data-inicio del elemento Strong
        const horaFin = horarioElement.getAttribute('data-fin'); //obtiene el valor de data-fin del elemento Strong (hora de inicio + duracion de servicio)
        const progreso = calcularProgreso(horaInicio, horaFin, fecha);// almacena como progreso la funcion realizada anteriormente pasando los parametros que requiere

        const progressBar = document.getElementById(idProgressBar); //Captura el elemento div con la barra de progreso
        const taskCat = document.getElementById(idTaskCat); //Captura el span con sus propiedades

        progressBar.style.width = progreso + '%'; //modifica el style="width: 0-100%" en el div

        if (progreso === 0) {
            taskCat.innerText = 'Pendiente';
            taskCat.className = 'task-cat cyan';
        } else if (progreso > 0 && progreso < 100) {
            taskCat.innerText = 'En Proceso';
            taskCat.className = 'task-cat deep-orange';
        } else if (progreso === 100) {
            taskCat.innerText = 'Completado';
            taskCat.className = 'task-cat green';
        }//Estados segun progresos

        
        
    }

    // Actualizar barras cada segundo
    setInterval(function() {
        actualizarProgreso('horario-sauna', 'progress-sauna', 'task-cat-sauna');
        actualizarProgreso('horario-tinaja', 'progress-tinaja', 'task-cat-tinaja');
        actualizarProgreso('horario-masaje', 'progress-masaje', 'task-cat-masaje');
    }, 1000); // actualiza la barra cada 1 segundo
});





</script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
      var elems = document.querySelectorAll('.modal');
      M.Modal.init(elems);
  });

  document.addEventListener('DOMContentLoaded', function() {
    var elems = document.querySelectorAll('.tooltipped');
    M.Tooltip.init(elems, {
      position: 'top',
    });
  });
</script>

<script>
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
</script>

<script>
  $(document).ready(function(){
$('.modal-trigger').on('click', function(){
      // Obtener los datos del cliente y la reserva seleccionada
      var abono = $(this).data('abono') || 0;
      var abonoImg = $(this).data('abonoimg') || 'https://via.placeholder.com/200x300';
      var diferencia = $(this).data('diferencia') || 0;
      var diferenciaImg = $(this).data('diferenciaimg');
      var descuento = $(this).data('descuento');
      var totalPagar = $(this).data('totalpagar');
      var tipoAbono = $(this).data('tipoabono');
      var tipoDiferencia = $(this).data('tipodiferencia');
      var consumos = $(this).data('consumo');
      

      // Insertar los datos en los elementos del modal
      $('#modalAbono').text(abono);
      $('#modalDiferencia').text(diferencia);
      
      $('#linkAbono').attr('href',abonoImg);
      $('#linkDiferencia').attr('href',diferenciaImg);
      $('#modalAbonoImg').attr('src',abonoImg);
      $('#modalDiferenciaImg').attr('src',diferenciaImg);
      
          // Validar si el descuento es nulo
          if (descuento == null || descuento == '') {
            $('#modalDescuento').text('0');
          } else {
            $('#modalDescuento').text(descuento + '%');
          }



      $('#modalTotalPagar').text(totalPagar);
      $('#modalTipoAbono').text(tipoAbono);
      $('#modalTipoDiferencia').text(tipoDiferencia);
      
      
      // Limpiar el contenido anterior de consumos en el modal
      $('#modalConsumo').empty();

      // Crear la tabla para los consumos
      var subtotalConsumo=0;
      var totalConsumo = 0;
      var subtotalServicio=0;

      var tablaConsumos = '<table class="highlight responsive-table centered">';

      tablaConsumos += '<thead><tr><th>Producto</th><th>Valor</th><th>Cantidad</th><th>SubTotal</th><th>Propina</th></tr></thead>';
      tablaConsumos += '<tbody>';

      // Iterar sobre los consumos y agregar filas a la tabla
      if (Array.isArray(consumos) && consumos.length > 0) {
          consumos.forEach(function(consumo, index) {
              if (Array.isArray(consumo.detalles_consumos) && consumo.detalles_consumos.length > 0) {
                  consumo.detalles_consumos.forEach(function(detalle, detalleIndex) {
                      tablaConsumos += '<tr>';
                      tablaConsumos += '<td>' + detalle.producto.nombre + '</td>';  // Cambia si tienes un nombre específico del producto
                      tablaConsumos += '<td>$' + detalle.producto.valor + '</td>';
                      tablaConsumos += '<td>'+'X' + detalle.cantidad_producto + '</td>';
                      tablaConsumos += '<td>$' + detalle.subtotal + '</td>';
                      tablaConsumos += '<td>$' + detalle.subtotal * 0.1 +'</td>';
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
          tablaConsumos += '<td>' + '<strong>SubTotal: $'+subtotalConsumo+'</strong>' + '</td>'; 
          tablaConsumos += '<td>' + '<strong>Total: $'+Math.trunc(totalConsumo)+'</strong>' + '</td>'; 
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
                      tablaServicios += '<td>$' + detalle.servicio.valor_servicio + '</td>';
                      tablaServicios += '<td>' +'X'+ detalle.cantidad_servicio + '</td>';
                      tablaServicios += '<td>$' + detalle.subtotal + '</td>';
                      subtotalServicio += detalle.subtotal;
                      tablaServicios += '</tr>';
                  });
              }
          });
          tablaServicios += '<tr>';
          tablaServicios += '<td>' + '</td>'; 
          tablaServicios += '<td>' + '</td>'; 
          tablaServicios += '<td>' + '</td>'; 
          tablaServicios += '<td>' + '<strong>Total:'+subtotalServicio+'</strong>' + '</td>'; 
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
        
        tablaResumen += '<thead><tr><th>Subtotal Consumo</th><th>Total Consumo</th><th>Total Servicios</th><th>Diferencia</th><th>SubTotal</th><th>Total</th></tr></thead>';
        tablaResumen += '<tbody>';

        tablaResumen += '<tr>';
        tablaResumen += '<td>' +'$'+ subtotalConsumo + '</td>'; 
        tablaResumen += '<td>' +'$'+ Math.trunc(totalConsumo) + '</td>'; 
        tablaResumen += '<td>' +'$'+ subtotalServicio + '</td>'; 
        tablaResumen += '<td>' +'$'+ totalPagar + '</td>'; 
        tablaResumen += '<td>' + '<strong> $' + SubTotalPagar+ '</strong>' + '</td>'; 
        tablaResumen += '<td>' + '<strong> $'+TotalPagarCP+'</strong>' + '</td>'; 
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

@endsection