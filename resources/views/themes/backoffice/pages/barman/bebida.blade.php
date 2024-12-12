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
@endsection