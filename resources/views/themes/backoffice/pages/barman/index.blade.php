@extends('themes.backoffice.layouts.admin')

@section('title','Bebidas y Cócteles')

@section('head')
<link href='{{ asset('assets/sortable/Sortable.min.css') }}' rel='stylesheet' />
@endsection

@section('breadcrumbs')
@endsection


@section('dropdown_settings')
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Bebidas</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
    


                    <div class="row">
                        <!-- Por Procesar -->
                        <div class="col s12 m4" id="por-procesar">
                            <h5>Por Procesar</h5>
                            <ul class="collection">
                                @foreach($productos->where('estado', 'por-procesar')->sortBy('creado') as $producto)
                                    <li class="collection-item avatar" data-id="{{ $producto->id }}">
                                        <i class="material-icons circle red">local_drink</i>
                                        <span class="title">{{ $producto->producto }} X{{$producto->cantidad_producto }}</span>
                                        <p>
                                            Cliente: {{ $producto->nombre_cliente }} <br>
                                            Ubicacion: {{ $producto->ubicacion }}
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    
                        <!-- En Preparación -->
                        <div class="col s12 m4" id="en-preparacion">
                            <h5>En Preparación</h5>
                            <ul class="collection">
                                @foreach($productos->where('estado', 'en-preparacion') as $producto)
                                <li class="collection-item avatar" data-id="{{ $producto->id }}">
                                    <i class="material-icons circle red">local_bar</i>
                                    <span class="title">{{ $producto->producto }} X{{$producto->cantidad_producto }}</span>
                                    <p>
                                        Cliente: {{ $producto->nombre_cliente }} <br>
                                        Ubicacion: {{ $producto->ubicacion }}
                                    </p>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    
                        <!-- Completado -->
                        <div class="col s12 m4" id="completado">
                            <h5>Completado</h5>
                            <ul class="collection">
                                @foreach($productos->where('estado', 'completado') as $producto)
                                <li class="collection-item avatar" data-id="{{ $producto->id }}">
                                    <i class="material-icons circle red">done_all</i>
                                    <span class="title">{{ $producto->producto }} X{{$producto->cantidad_producto }}</span>
                                    <p>
                                        Cliente: {{ $producto->nombre_cliente }} <br>
                                        Ubicacion: {{ $producto->ubicacion }}
                                    </p>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    
                    
                    


            </div>
        </div>
    </div>
</div>
@endsection


@section('foot')
<script src='{{ asset('assets/sortable/Sortable.min.js')}}'></script>

<script>
    ['por-procesar', 'en-preparacion', 'completado'].forEach(function (id) {
        new Sortable(document.getElementById(id).querySelector('.collection'), {
            group: 'shared',
            animation: 150,
            onEnd: function (evt) {
                const detalleId = evt.item.getAttribute('data-id');
                const nuevoEstado = evt.to.parentNode.id;

                

                // Actualizar estado en el servidor
                fetch('barman/detalles-consumos/' + detalleId + '/actualizar-estado', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ estado: nuevoEstado })
                }).then(response => {
                    if (!response.ok) {
                        console.error('Error al actualizar el estado');
                    }
                }).catch(error => console.error(error));
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.Echo !== 'undefined') {
            window.Echo.channel('consumo-canal')
                .listen('NuevoConsumoAgregado', (e) => {
                    console.log(e.mensaje);
                    
                    // Refrescar la página o hacer una llamada AJAX para actualizar los productos
                    location.reload();

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
                        icon: "success",
                        title: e.mensaje
                    });
                });
        } else {
            console.error("Echo no está definido, verifica la configuración.");
        }
    });
</script>
@endsection