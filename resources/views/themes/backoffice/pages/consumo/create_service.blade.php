@extends('themes.backoffice.layouts.admin')

@section('title', 'Ingresar Consumo')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.show', $venta->id_reserva) }}">Servicios para reserva del cliente</a></li>
<li>Ingresar Servicios Extra</li>
@endsection

@section('content')

<div class="section">
    <p class="caption">Ingrese los datos del servicios extra para la venta.</p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2">
                <div class="card-panel">
                    <h4 class="header">Servicios extra para la venta de
                        <strong>{{$venta->reserva->cliente->nombre_cliente}}</strong>
                    </h4>
                    <div class="row">
                        <form class="col s12" method="post"
                            action="{{route('backoffice.venta.consumo.service_store', $venta)}}">
                            {{csrf_field()}}



                            <div class="row">
                                <div class="input-field col s12 m6 l4" hidden>
                                    <input id="id_venta" type="hidden" name="id_venta" value="{{$venta->id}}" required>
                                </div>

                                <div class="card">
                                    <div class="card-content gradient-45deg-light-blue-cyan">
                                        <h5 class="white-text">Selecciona tus servicios</h5>
                                    </div>


                                    <div class="card-tabs">
                                        <ul class="tabs tabs-fixed-width">
                                            @foreach($servicios as $servicio)
                                            <li class="tab">
                                                <a href="" class="servicio" data-id="{{ $servicio->id }}"
                                                    data-nombre="{{ $servicio->nombre_servicio }}"
                                                    data-precio="{{ $servicio->valor_servicio }}">{{
                                                    $servicio->nombre_servicio }}</a>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    <div class="card-content grey lighten-4">
                                        <!-- Aquí se mostrarán los servicios seleccionados -->
                                        <div id="servicios_seleccionados"></div>
                                    </div>
                                </div>



                            </div>

                    </div>








                    <div class="row">
                        <div class="input-field col s12">
                            <button class="btn waves-effect waves-light right" type="submit">Guardar
                                <i class="material-icons right">send</i>
                            </button>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@endsection

@section('foot')

<script>
    function eliminarServicio (id) { 
        Swal.fire({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esta acción!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminarlo'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#servicio_' + id).remove();
            Swal.fire(
                'Eliminado',
                'El servicio ha sido eliminado.',
                'success'
            );
        }
    });
     }

    $(document).ready(function() {
        // Inicializar manualmente los tabs de Materialize
        $('ul.tabs').tabs();



        // Desasociar cualquier evento anterior y luego asociar el evento click
        $('.servicio').off('click').on('click', function(event) {
            event.preventDefault(); // Evitar el comportamiento por defecto de los tabs

            // Obtener los datos del servicio seleccionado
            var id = $(this).data('id');
            var nombre = $(this).data('nombre');
            var precio = $(this).data('precio');



            // Verificar si el servicio ya fue seleccionado
            if ($('#servicio_' + id).length === 0) {
                // Si no ha sido seleccionado, añadirlo a la lista de servicios seleccionados
                $('#servicios_seleccionados').append(
                    '<div class="row" id="servicio_' + id + '">' +
                        '<div class="col s1">' +
                            '<a href="javascript:void(0);" class="" onclick="eliminarServicio('+ id +')"><i class="material-icons" style="padding-top:25px; color:red;">clear</i></a>'+
                        '</div>'+
                        '<div class="col s3">' +
                            '<blockquote><h5>' + nombre + '</h5></blockquote>' +
                        '</div>' +
                        '<div class="col s4">' +
                            '<h5>$' + precio + '</h5>' +
                        '</div>' +
                        '<div class="col s4">' +
                            '<input type="number" name="servicios[' + id + '][cantidad]" placeholder="Cantidad" min="1">' +
                            '<input type="hidden" name="servicios[' + id + '][precio]" value="' + precio + '">' +
                        '</div>' +
                    '</div>'
                );

            } else {
                // Si ya fue seleccionado, simplemente ignorar o mostrar mensaje
                // Swal.fire({
                    //     icon: 'warning',
                    //     title: 'Servicio ya seleccionado',
                //     text: 'Este servicio ya ha sido agregado a la lista.',
                //     confirmButtonText: 'OK'
                // });
                
                const Toast = Swal.mixin({
                    toast: true,
                    position: "top",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });
                
                Toast.fire({
                    icon: "error",
                    title: "El servicio ya fue incorporado a la lista, agregue la cantidad"
                });
                
            }

        });

    });
</script>


@endsection