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
                .listen('Consumos.NuevoConsumoAgregado', (e) => {
                    // Mostrar el Sweet Alert
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "",
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

                    
                    // // Recargar la página después de mostrar el Toast
                    // setTimeout(() => {
                    //     location.reload();
                    // }, 3000); // Espera 3 segundos (3000ms) para recargar la página


                // Agregar nuevo consumo a la lista "Por Procesar"
                const listaPorProcesar = document.querySelector('#por-procesar .collection');
                e.productos.forEach((producto) => {
                    // Verificar si ya existe el producto en la lista
                    const existente = listaPorProcesar.querySelector(`[data-id="${producto.id}"]`);

                    if (existente) {
                        // Actualizar cantidad del producto existente
                        const cantidadSpan = existente.querySelector('.cantidad');
                        cantidadSpan.textContent = `X${producto.cantidad}`;
                    } else {
                        // Crear un nuevo elemento para el producto
                        const nuevoElemento = document.createElement('li');
                        nuevoElemento.classList.add('collection-item', 'avatar');
                        nuevoElemento.setAttribute('data-id', producto.id);
                        nuevoElemento.innerHTML = `
                            <i class="material-icons circle red">local_drink</i>
                            <span class="title">${producto.nombre} <span class="cantidad">X${producto.cantidad}</span></span>
                            <p>
                                Cliente: ${producto.cliente} <br>
                                Ubicación: ${producto.ubicacion}
                            </p>
                        `;
                        listaPorProcesar.appendChild(nuevoElemento);
                    }
                });
            });


            // Escuchar cambios de estado            
            window.Echo.channel('consumo-canal-actualizar')
            .listen('Consumos.EstadoConsumoActualizado', (e) => {

                const detalleId = e.detalleId;
                const nuevoEstado = e.estado;
                


                // Encontrar el elemento actual
                const elemento = document.querySelector(`[data-id="${detalleId}"]`);
                
                if (elemento) {
                    // Mover el elemento a la nueva lista
                    const nuevaLista = document.querySelector(`#${nuevoEstado} .collection`);
                    nuevaLista.appendChild(elemento);
                }

                estado = "";

                switch (nuevoEstado) {
                    case 'por-procesar':
                        estado = 'Pedido por procesar';
                        break;

                    case 'en-preparacion':
                        estado = 'Preparando pedido';
                        break;

                    case 'completado':
                        estado = 'Pedido completado';
                        break;
                
                    default:
                        estado = 'Estado desconocido';
                        break;
                }
                

                const Toast = Swal.mixin({
                        toast: true,
                        position: "top-right",
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
                        title: estado
                    });


            });



        } else {
            console.error("Echo no está definido, verifica la configuración.");
        }

    });
</script>

@endsection