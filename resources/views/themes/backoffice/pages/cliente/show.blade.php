@extends('themes.backoffice.layouts.admin')

@section('title', $cliente->nombre_cliente)

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.cliente.index')}}">Clientes del Sistema</a></li>
<li>{{$cliente->nombre_cliente}}</li>
@endsection

@section('dropdown_settings')
<li><a href="{{ route('backoffice.reserva.create',$cliente->id) }}" class="grey-text text-darken-2">Crear Reserva</a>
</li>
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Cliente:</strong> {{$cliente->nombre_cliente}}</p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8">
                <div class="card">
                    <div class="card-content">



                        <span class="card-title activator grey-text text-darken-4">{{$cliente->nombre_cliente}}</span>
                        <p>
                            @if (is_null($cliente->whatsapp_cliente))
                            <i class="material-icons">perm_phone_msg</i> No Registra
                            @else
                            <i class="material-icons">perm_phone_msg</i> <a
                                href="https://api.whatsapp.com/send?phone={{$cliente->whatsapp_cliente}}"
                                target="_blank">+{{$cliente->whatsapp_cliente}}</a>
                            @endif

                        </p>
                        <p>

                            @if (is_null($cliente->instagram_cliente))
                            <i class="material-icons">perm_identity</i> No Registra
                            @else
                            <i class="material-icons">perm_identity</i> <a
                                href="https://www.instagram.com/{{$cliente->instagram_cliente}}"
                                target="_blank">{{$cliente->instagram_cliente}}</a>
                            @endif


                        </p>
                        <p>

                            @if (is_null($cliente->correo))
                            <i class="material-icons">email</i> No Registra
                            @else
                            <i class="material-icons">email</i> <a href="mailto:{{$cliente->correo}}"
                                target="_blank">{{$cliente->correo}}</a>
                            @endif


                        </p>




                    </div>
                    <div class="card-action">
                        <a href="{{route('backoffice.cliente.edit', $cliente) }}" class="purple-text">Editar</a>
                        {{-- <a href="#" style="color: red" onclick="enviar_formulario()">Eliminar</a> --}}
                    </div>
                </div>
            </div>


            <div class="col s12 m4">
                @include('themes.backoffice.pages.cliente.includes.cliente_nav')
            </div>

            @include('themes.backoffice.pages.cliente.includes.modal_reserva')


        </div>
    </div>
</div>
</div>

<form method="post" action="{{route('backoffice.cliente.destroy', $cliente) }} " name="delete_form">
    {{csrf_field()}}
    {{method_field('DELETE')}}
</form>
@endsection

@section('foot')
<script>
    function enviar_formulario()
 {
     Swal.fire({
         title: "¿Deseas eliminar este cliente?",
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

$(document).ready(function () {

    $('.tooltipped').tooltip();

    $('.collapsible').collapsible();

    $('.modal').modal();

});

</script>

<script>
    $(document).ready(function(){
        $('.modal-trigger').on('click', function(){
                // Obtener los datos del cliente y la reserva seleccionada
                var clienteNombre = $(this).data('cliente');
                var fechaReserva = $(this).data('fecha');
                var observacionReserva = $(this).data('observacion') || 'No registra';
                var masajeReserva = $(this).data('masaje');
                var personasReserva = $(this).data('personas');


                var programaReserva = $(this).data('programa');
                var abonoReserva = $(this).data('abono');
                var tipoAbonoReserva = $(this).data('tipo_abono');

                var diferenciaReserva = $(this).data('diferencia');
                var tipoDiferenciaReserva = $(this).data('tipo_diferencia');

                var saunaReserva = $(this).data('sauna');
                var tinajaReserva = $(this).data('tinaja');
                
                var horaMasajesReserva = $(this).data('horariomasajes');

                var saunaReservaFin = $(this).data('sauna-fin');
                var tinajaReservaFin = $(this).data('tinaja-fin');
                
                var horaMasajesReservaFin = $(this).data('horariomasajes-fin');

                

                var menusReserva = $(this).data('menus');

                // Insertar los datos en los elementos del modal
                $('#modalClienteNombre').text(clienteNombre);
                $('#modalFechaReserva').text(fechaReserva);
                $('#modalObservacionReserva').text(observacionReserva);
                $('#modalMasajeReserva').text(masajeReserva);
                $('#modalPersonasReserva').text(personasReserva);


                $('#modalPrograma').text(programaReserva);
                $('#modalAbono').text(abonoReserva);
                $('#modalTipoAbono').text(tipoAbonoReserva);

                $('#modalDiferencia').text(diferenciaReserva);
                $('#modalTipoDiferencia').text(tipoDiferenciaReserva);
                
                $('#modalSauna').text(saunaReserva);
                $('#modalTinaja').text(tinajaReserva);
                $('#modalMasaje').text(horaMasajesReserva);

                $('#modalSaunaFin').text(saunaReservaFin);
                $('#modalTinajaFin').text(tinajaReservaFin);
                $('#modalMasajeFin').text(horaMasajesReservaFin);

            // Procesar los menús y poblar la tabla
            var menuTableBody = $('#modalMenusTable');
            menuTableBody.empty(); // Limpiar tabla anterior

            if (menusReserva && Array.isArray(menusReserva) && menusReserva.length > 0) {
                menusReserva.forEach(function(menu) {
                    var alergiasClass = menu.alergias !== 'No registra' ? 'red-text' : '';
                    var observacionClass = menu.observacion !== 'No registra' ? 'red-text' : '';

                    
                    var row = `<tr>
                        <td>${menu.entrada}</td>
                        <td>${menu.fondo}</td>
                        <td>${menu.acompanamiento}</td>
                        <td class="${alergiasClass}">${menu.alergias}</td>
                        <td class="${observacionClass}">${menu.observacion}</td>
                    </tr>`;
                    menuTableBody.append(row);
                });
            } else {
                menuTableBody.html('<tr><td colspan="3" class="center">No registra</td></tr>');
            }

            // Abrir el modal
            $('#modalReserva').modal('open');
        });
    });
</script>
@endsection