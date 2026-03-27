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
<a href="#!"
   id="btnPushDesktop"
   onclick="event.preventDefault(); activarNotificacionesPush();"
   class="btn green hide-on-med-and-down">
    <i class="material-icons left">notifications</i>
    Activar notificaciones
</a>

<div id="btnPushFab" class="fixed-action-btn hide-on-large-only">
    <a href="#!"
       onclick="event.preventDefault(); activarNotificacionesPush();"
       class="btn-floating btn-large red">
        <i class="large material-icons">notifications</i>
    </a>
</div>
    
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

                    <ul class="collection pedidos">
                        @foreach(($pedidos['completado'] ?? collect()) as $idConsumo => $items)
                                @php 
            $first = $items->first();
            $pedidoKey = $first->pedido_key; 
        @endphp

      <li class="collection-item pedido" data-pedido-key="{{ $pedidoKey }}" data-id-consumo="{{ $first->id_consumo }}"
    data-pedido-creado="{{ \Carbon\Carbon::parse($first->creado)->format('Y-m-d H:i:s') }}">
                            <div style="display:flex; gap:10px; align-items:flex-start;">
                            <i class="material-icons circle green" style="color:white; padding:8px; border-radius:50%;">done_all</i>

                            <div>
                                <div style="font-weight:600;">{{ $first->nombre_cliente }}</div>
                                <div class="grey-text text-darken-1">Ubicación: {{ $first->ubicacion }}</div>

                                <ul class="productos">
                                @foreach($items as $p)
                                    <li data-id="{{ $p->id }}">- {{ $p->producto }} <span class="cantidad">X{{ $p->cantidad_producto }}</span></li>
                                @endforeach
                                </ul>
                            </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    </div>

                    <!-- Entregado -->
<div class="col s12 m6" id="entregado">
  <h5>Entregado</h5>

  <ul class="collection pedidos">
    @foreach(($pedidos['entregado'] ?? collect()) as $idConsumo => $items)
        @php 
            $first = $items->first();
            $pedidoKey = $first->pedido_key; 
        @endphp

      <li class="collection-item pedido" data-pedido-key="{{ $pedidoKey }}" data-id-consumo="{{ $first->id_consumo }}"
    data-pedido-creado="{{ \Carbon\Carbon::parse($first->creado)->format('Y-m-d H:i:s') }}">
        <div style="display:flex; gap:10px; align-items:flex-start;">
          <i class="material-icons circle green" style="color:white; padding:8px; border-radius:50%;">local_bar</i>

          <div>
            <div style="font-weight:600;">{{ $first->nombre_cliente }}</div>
            <div class="grey-text text-darken-1">Ubicación: {{ $first->ubicacion }}</div>

            <ul class="productos">
              @foreach($items as $p)
                <li data-id="{{ $p->id }}">- {{ $p->producto }} <span class="cantidad">X{{ $p->cantidad_producto }}</span></li>
              @endforeach
            </ul>
          </div>
        </div>
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
['completado', 'entregado'].forEach(function (colId) {

    const ul = document.querySelector(`#${colId} .pedidos`);
    if (!ul) return;

    new Sortable(ul, {
        group: 'pedidos-garzon',
        animation: 150,
        draggable: '.pedido',
        filter: '.productos, .productos *',
        onEnd: function (evt) {

            const idConsumo   = evt.item.getAttribute('data-id-consumo');
            const pedidoCreado = evt.item.getAttribute('data-pedido-creado');
            const nuevoEstado = evt.to.closest('[id]').id;

            fetch(`/barman/consumos/${idConsumo}/actualizar-estado`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ estado: nuevoEstado, pedido_creado: pedidoCreado })
            }).catch(console.error);
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

                const nuevoEstado = e.estado;
                const pedidoKey = e.detalleId; // pedido_key
                const data = e.producto || {};

                // Solo nos interesan completado/entregado en esta vista
                if (nuevoEstado !== 'completado' && nuevoEstado !== 'entregado') return;

                let pedidoEl = document.querySelector(`[data-pedido-key="${pedidoKey}"]`);
                let nuevaLista = document.querySelector(`#${nuevoEstado} .pedidos`);
                if (!nuevaLista) return;

                // Si no existe, lo creamos con la data del evento
                if (!pedidoEl) {
                    pedidoEl = document.createElement('li');
                    pedidoEl.className = 'collection-item pedido';
                    pedidoEl.setAttribute('data-pedido-key', data.pedido_key || pedidoKey);
                    pedidoEl.setAttribute('data-id-consumo', data.pedido_id || data.id_consumo || '');
                    pedidoEl.setAttribute('data-pedido-creado', data.pedido_creado || '');

                    const icon = (nuevoEstado === 'completado') ? 'done_all' : 'playlist_add_check';

                    pedidoEl.innerHTML = `
                    <div style="display:flex; gap:10px; align-items:flex-start;">
                        <i class="material-icons circle green" style="color:white; padding:8px; border-radius:50%;">${icon}</i>
                        <div>
                        <div style="font-weight:600;">${data.cliente || ''}</div>
                        <div class="grey-text text-darken-1">Ubicación: ${data.ubicacion || ''}</div>
                        <ul class="productos"></ul>
                        </div>
                    </div>
                    `;

                    // Render productos del pedido (items)
                    const ulProductos = pedidoEl.querySelector('.productos');
                    (data.items || []).forEach(p => {
                        const li = document.createElement('li');
                        li.setAttribute('data-detalle-id', p.id_detalle);
                        li.innerHTML = `- ${p.nombre} <span class="cantidad">X${p.cantidad}</span>`;
                        ulProductos.appendChild(li);
                    });
                }

                // mover a la columna destino
                nuevaLista.appendChild(pedidoEl);

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
</script>


{{-- <script>
    document.addEventListener('DOMContentLoaded', function () { 
        if (typeof window.Echo !== 'undefined') {
            // Escuchar cambios de estado            
            window.Echo.channel('consumo-canal-actualizar')
            .listen('Consumos.EstadoConsumoActualizado', (e) => {
                const audio = new Audio('/sounds/notificacionv2.mp3');

                // const pedidoId    = e.detalleId; // en tu evento ahora viaja id_consumo
                const nuevoEstado = e.estado;

                const pedidoKey = e.detalleId;

                const pedidoEl = document.querySelector(`[data-pedido-key="${pedidoKey}"]`);
                const nuevaLista = document.querySelector(`#${nuevoEstado} .pedidos`);

                if (!pedidoEl || !nuevaLista) return;

                // Si el garzón marca entregado, en barman debe desaparecer:
                if (nuevoEstado === 'entregado') {
                    pedidoEl.remove();
                    return;
                }

                nuevaLista.appendChild(pedidoEl);

                audio.play();

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
                    case 'por-procesar':
                        estado = 'Pedido por procesar';
                        break;

                    case 'en-preparacion':
                        estado = 'Preparando pedido';
                        break;

                    case 'completado':
                        estado = 'Pedido completado';
                        break;

                    case 'entregado':
                        estado = 'Pedido entregado al cliente';
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
</script> --}}

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