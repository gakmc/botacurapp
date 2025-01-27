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


                @php
                    $indexPedido = 0;
                @endphp

                <div class="row">

                    <!-- Completado -->
                    <div class="col s12 m6" id="completado">
                        <h5>Completado</h5>
                        <ul class="collection">
                            @foreach($productos->where('estado', 'completado') as $producto)
                            <li class="collection-item avatar" data-id="{{ $producto->id }}">
                                <i class="material-icons circle green">done_all</i>
                                <span class="title">{{ $producto->producto }} X{{$producto->cantidad_producto }}</span>
                                <p>
                                    Cliente: {{ $producto->nombre_cliente }} <br>
                                    Ubicacion: {{ $producto->ubicacion }}
                                </p>
                            </li>
                            @php
                                $indexPedido++
                            @endphp
                            @endforeach
                        </ul>
                    </div>

                    <!-- Entregado -->
                    <div class="col s12 m6" id="entregado">
                        <h5>Entregado</h5>
                        <ul class="collection">
                            @foreach($productos->where('estado', 'entregado') as $producto)
                            <li class="collection-item avatar" data-id="{{ $producto->id }}">
                                <i class="material-icons circle green">local_bar</i>
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
    ['completado', 'entregado'].forEach(function (id) {
        new Sortable(document.getElementById(id).querySelector('.collection'), {
            group: 'shared',
            animation: 150,
            onEnd: function (evt) {
                const detalleId = evt.item.getAttribute('data-id');
                const nuevoEstado = evt.to.parentNode.id;

                // Actualizar estado en el servidor
                fetch('bebidas/detalles-consumos/' + detalleId + '/actualizar-estado', {
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
        // Escuchar cambios de estado            
        window.Echo.channel('consumo-canal-actualizar')
        .listen('Consumos.EstadoConsumoActualizado', (e) => {
            const detalleId = e.detalleId;
            const nuevoEstado = e.estado;

            // Buscar el elemento en la lista actual
            let elemento = document.querySelector(`[data-id="${detalleId}"]`);
            
            if (elemento) {
                // Mover el elemento a la nueva lista
                const nuevaLista = document.querySelector(`#${nuevoEstado} .collection`);
                nuevaLista.appendChild(elemento);
            } else {
                // Si el elemento no existe, crearlo dinámicamente
                const nuevaLista = document.querySelector(`#${nuevoEstado} .collection`);
                const nuevoElemento = document.createElement('li');
                nuevoElemento.classList.add('collection-item', 'avatar');
                nuevoElemento.setAttribute('data-id', detalleId);
                nuevoElemento.innerHTML = `
                    <i class="material-icons circle green">${nuevoEstado === 'completado' ? 'done_all' : 'local_bar'}</i>
                    <span class="title">${e.producto.nombre} X${e.producto.cantidad}</span>
                    <p>
                        Cliente: ${e.producto.cliente} <br>
                        Ubicación: ${e.producto.ubicacion}
                    </p>
                `;
                nuevaLista.appendChild(nuevoElemento);
            }

            // Mostrar un toast
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

            let estado = "";
            switch (nuevoEstado) {
                case 'completado':
                    estado = 'Pedido completado';
                    break;
                case 'entregado':
                    estado = 'Pedido entregado';
                    break;
                default:
                    estado = 'Estado desconocido';
                    break;
            }

            Toast.fire({
                icon: "success",
                title: estado
            });
        });
    }
});
</script>

<script>
    $(document).ready(function () {
        
        cantidadPedidos = {!! $indexPedido !!};
    
        if (cantidadPedidos > 0) {
            $('#bebidasGarzon').val(`Bebidas <span class="new badge" data-badge-caption="Pedidos">${cantidadPedidos}</span>`)
        }
    
    });
</script>

{{-- <script>
    document.addEventListener('DOMContentLoaded', function () { 
        if (typeof window.Echo !== 'undefined') {
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
                    case 'completado':
                        estado = 'Pedido completado';
                        break;

                    case 'entregado':
                        estado = 'Pedido Entregado';
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

        }
     });
</script> --}}
@endsection