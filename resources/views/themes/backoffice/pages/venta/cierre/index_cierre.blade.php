@extends('themes.backoffice.layouts.admin')

@section('title', 'Gestión Consumo')

@section('breadcrumbs')
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{route ('backoffice.reserva.listar') }}" class="grey-text text-darken-2">Todas las Reservas</a></li> --}}
@endsection

@section('head')
<style>
    .dropdown-content {
        position: absolute !important;
        top: auto !important;
        width: auto !important;
        min-width: 150px !important;
        max-width: 250px !important;
    }

    /* Centrar el botón del dropdown */
    td .dropdown-button {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 5px;
    }
</style>
@endsection

@section('content')

<div class="section">
    <a href="?page=1"><p class="caption"><strong>Reservas de hoy: {{ now()->format('d-m-Y') }}</strong></p></a>

    {{-- <div class="row">
        <div class="col s2 green-text offset-s2">
            <i class='material-icons left'>done_all</i>Registro completo
        </div>
        <div class="col s2 green-text offset-s2">
            <i class='material-icons left'>check</i>Registro incompleto
        </div>
        <div class="col s2 red-text offset-s1">
            <i class='material-icons left'>close</i>No Registra
        </div> 
    </div> --}}
    
    <div class="divider"></div>
    <div id="basic-form" class="section">

        <div class="col s12" data-tipo="sauna">
            <div class="card-panel animate__animated animate__fadeInRight"
                style="--animate-delay: 1s; --animate-duration: 2s;">       


            @if ($reservas->isEmpty())
                <h5 class="center">
                    No se registran reservas para el día de hoy.
                </h5>
            @else

                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                {{-- <th>WhatsApp</th> --}}
                                <th>Ubicacion</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                
                        <tbody>

                        @foreach ($reservas as $reserva)
                        @php
                            $asignar = false;
                            foreach ($asignados as $index => $asignado) {
                                $fecha = \Carbon\Carbon::parse($asignado->fecha)->format('d-m-Y');
                                if ($fecha === $reserva->fecha_visita) {
                                    $asignar = true;
                                }
                            }
                        @endphp

                        @php
                            $visita   = $reserva->visitas->last();
                            $visitas  = $reserva->visitas;
                            $consumo = $reserva->venta->consumo;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{route('backoffice.reserva.show', $reserva)}}">
                                    {{$reserva->cliente->nombre_cliente ?? 'Desconocido'}}
                                </a>
                            </td>
                            {{-- <td>
                                @if(is_null($reserva->cliente->whatsapp_cliente)) 
                                    No Registra
                                @else
                                    <a href="https://api.whatsapp.com/send?phone={{$reserva->cliente->whatsapp_cliente}}" target="_blank">+{{$reserva->cliente->whatsapp_cliente}}</a>
                                @endif
                            </td> --}}
                            <td>
                                @if (!is_null($visita->id_ubicacion))
                                    {{$visita->ubicacion->nombre ?? 'No registra ubicación'}}
                                @else
                                    <a id="noRegistra" href="{{route('backoffice.visita.edit_ubicacion',['visitum'=>$reserva->visitas->first()])}}">No registra ubicación.</a>
                                @endif
                            </td>
                            <td>
                                {{-- <a class='dropdown-ventas btn-flat' href='#' data-activates='dropdown-{{$reserva->id}}'><i class='material-icons'>more_vert</i></a>


                                <ul id='dropdown-{{$reserva->id}}' class='dropdown-content'>
                                    @if (is_null($reserva->venta->diferencia_programa))
                                        @if(!empty($consumo))
        
                                                <li><a href="#modal-{{$reserva->id}}" class="modal-trigger"><i class="material-icons">remove_red_eye</i>Ver Consumo</a></li>
                                            
                                        @endif
                                        

                                        <li><a href="#modalVenta" class="modal-trigger" data-id="{{ $reserva->venta->id }}" data-diferencia="{{ $reserva->venta->diferencia_programa }}" data-totalpagar="{{$reserva->venta->total_pagar}}" data-consumo="{{$consumo}}"><i class="material-icons">view_list</i>Detalles Venta</a></li>

                                        <li><a href="{{ route('backoffice.venta.consumo.create', $reserva->venta) }}"><i class="material-icons">local_bar</i>Ingresar Consumo</a></li>
        
        
                                        <li><a href="{{ $asignar ? route('backoffice.reserva.venta.cerrar', ['reserva' => $reserva, 'ventum' => $reserva->venta]) : 'javascript:void(0)' }}" class="collection-item center-align valign-wrapper left {{ !$asignar ? 'btn-alerta' : '' }}"><i class="material-icons">attach_money</i>Cerrar venta</a></li>
                            
                                    @else
                                        <li><a href="{{ route('backoffice.venta.pdf', $reserva) }}" target="_blank"><i class="material-icons">picture_as_pdf</i>PDF venta</a></li>
                                    @endif
                                </ul> --}}



                                @if (is_null($reserva->venta->diferencia_programa))
                                    @if(!empty($consumo))
                                        
                                        <a href="#modal-{{$reserva->id }}" class="btn-floating btn-small waves-effect waves-light blue modal-trigger tooltipped" data-position="bottom" data-tooltip="Ver consumo"><i class="material-icons">remove_red_eye</i></a>
                                    @endif

                                    <a href="#modalVenta" class="btn-floating btn-small waves-effect waves-light purple  modal-trigger tooltipped" data-position="bottom" data-tooltip="Detalles Venta" data-id="{{ $reserva->venta->id }}" data-diferencia="{{ $reserva->venta->diferencia_programa }}" data-totalpagar="{{$reserva->venta->total_pagar}}" data-consumo="{{$consumo}}"><i class="material-icons">view_list</i></a>

                                    <a href="{{ route('backoffice.venta.consumo.create', $reserva->venta) }}" class="btn-floating btn-small waves-effect waves-light pink tooltipped" data-position="bottom" data-tooltip="Ingresar consumo" ><i class="material-icons">local_bar</i></a>

                                    <a href="{{ $asignar ? route('backoffice.reserva.venta.cerrar', ['reserva' => $reserva, 'ventum' => $reserva->venta]) : 'javascript:void(0)' }}" class="btn-floating btn-small waves-effect waves-light green {{ !$asignar ? 'btn-alerta' : '' }} tooltipped" data-position="bottom" data-tooltip="Cerrar venta"><i class="material-icons">attach_money</i></a>

                                @else
                                    <a href="{{ route('backoffice.venta.pdf', $reserva) }}" class="btn-floating btn-small waves-effect waves-light red tooltipped" data-position="bottom" data-tooltip="PDF venta" target="_blank"><i class="material-icons">picture_as_pdf</i>PDF venta</a>
                                @endif




                            </td>

                            
                        </tr>
                        @endforeach
                        
                        
                        
                        </tbody>
                    </table>
                

                
                
            @endif
 
            
        </div>
        @if ($reservas->isNotEmpty())

            @foreach ($reservas as $reserva)
                
                @include('themes.backoffice.pages.reserva.includes.modal_boleta', ['reserva' => $reserva])

                @include('themes.backoffice.pages.reserva.includes.modal_venta', ['reserva' => $reserva])

            @endforeach
        @endif
        </div>
    </div>
</div>
@endsection

@section('foot')
{{-- Inicializar Materialize --}}
<script>

    $(document).ready(function () {
        $('.modal').modal();

        $('.dropdown-ventas').dropdown({
                alignment: 'right', // Alinea a la derecha
                constrainWidth: false, // No limita el ancho
                coverTrigger: false, // No cubre el botón
                hover: false, // Solo abre al hacer clic
                inDuration: 300, 
                outDuration: 200, 
                belowOrigin: true,
                gutter: 0, // Ajusta el espacio para evitar que se desplace
        });

    });


</script>

{{-- Alerta de no registrar ubicación --}}
<script>
    $(document).ready(function () {
        $('.collection-item').on('click', function (event) {
            let noRegistra = $(this).closest('tr').find('#noRegistra').length > 0;

            if (noRegistra) {
                event.preventDefault();
                
                Swal.fire({
                    toast: true,
                    icon: 'warning',
                    title: 'Debes agregar la ubicación antes de ingresar el consumo.',
                    color: 'white',
                    iconColor: 'yellow',
                    background: "#039B7B",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });
            }
        });
    });
</script>

{{-- Poblar el modal de venta --}}

@if ($reservas->isNotEmpty())
<script>
    function formatCLP(number) {
        return isNaN(number) ? '$0' : '$' + parseInt(number, 10).toLocaleString('es-CL');
    }
  
    $(document).ready(function(){
      $('.modal-trigger').on('click', function(){
            // Obtener los datos del cliente y la reserva seleccionada
  
            var abonoImg = $(this).data('abonoimg');
            var diferencia = $(this).data('diferencia') || 0;
  
            var descuento = $(this).data('descuento');
            var totalPagar = $(this).data('totalpagar');
  
            var consumo = $(this).data('consumo');

            
            
                // Validar si el descuento es nulo
                if (descuento == null || descuento == '') {
                  $('#modalDescuento').text(formatCLP(0));
                } else {
                  $('#modalDescuento').text(formatCLP(descuento));
                }
  
  
  
            $('#modalTotalPagar').text(formatCLP(totalPagar));
            
            
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
                    
                tablaServicios += '<tr>';
                tablaServicios += '<td>' + '</td>'; 
                tablaServicios += '<td>' + '</td>'; 
                tablaServicios += '<td>' + '</td>'; 
                tablaServicios += '<td class="right">' + '<strong>Total:'+formatCLP(subtotalServicio)+'</strong>' + '</td>'; 
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
@endif

{{-- Ruta de asignación --}}
@if ($reservas->isNotEmpty())
<script>
    const rutaAsignacion = "{{ route('backoffice.asignacion.create') . '?' .\Carbon\Carbon::parse($reserva->fecha_visita)->format('Y-m-d') }}";
</script>
@endif

{{-- Alerta de reserva sin equipo asignado --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Detectar clic en enlaces con clase btn-alerta
        document.querySelectorAll('.btn-alerta').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault(); // Evita navegación
                Swal.fire({
                    icon: 'warning',
                    title: 'No disponible',
                    text: 'Esta reserva aún no tiene un equipo asignado.',
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    confirmButtonText: 'Asignar',
                    cancelButtonText: 'Entendido',
                }).then((result)=>{
                    if (result.isConfirmed) {
                        window.location.href = rutaAsignacion;
                    }
                });
            });
        });
    });
</script>
@endsection