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

  <!-- LISTA EXTERNA: pedidos -->
  <ul class="collection pedidos">
    @foreach(($pedidos['por-procesar'] ?? collect()) as $idConsumo => $items)
        @php 
            $first = $items->first();
            $pedidoKey = $first->pedido_key; 
        @endphp

      <li class="collection-item pedido" data-pedido-key="{{ $pedidoKey }}" data-origen="{{ $first->origen }}"
    data-pedido-id="{{ $first->origen === 'venta_directa' ? $first->venta_directa_id : $first->id_consumo }}"
    data-pedido-creado="{{ \Carbon\Carbon::parse($first->creado)->format('Y-m-d H:i:s') }}">
        <div class="pedido-header" style="display:flex; gap:10px; align-items:flex-start;">
          <i class="material-icons circle red" style="color:white; padding:8px; border-radius:50%;">local_drink</i>

          <div>
            <div style="font-weight:600;">{{ $first->nombre_cliente }} <small class="grey-text">| {{ \Carbon\Carbon::parse($first->creado)->format("H:i")  }}</small></div>
            <div class="grey-text text-darken-1">Ubicación: {{ $first->ubicacion }}</div>

            <!-- LISTA INTERNA: productos -->
            <ul class="productos">
              @foreach($items as $p)
                <li data-detalle-id="{{ $p->id }}">
                  - {{ $p->producto }} <span class="cantidad">X{{ $p->cantidad_producto }}</span>
                </li>
              @endforeach
            </ul>
          </div>
        </div>
      </li>
    @endforeach
  </ul>
</div>
                    
                        <!-- En Preparación -->
<div class="col s12 m4" id="en-preparacion">
  <h5>En Preparación</h5>

  <ul class="collection pedidos">
    @foreach(($pedidos['en-preparacion'] ?? collect()) as $idConsumo => $items)
        @php 
            $first = $items->first();
            $pedidoKey = $first->pedido_key; 
        @endphp

      <li class="collection-item pedido" data-pedido-key="{{ $pedidoKey }}" data-origen="{{ $first->origen }}"
    data-pedido-id="{{ $first->origen === 'venta_directa' ? $first->venta_directa_id : $first->id_consumo }}"
    data-pedido-creado="{{ \Carbon\Carbon::parse($first->creado)->format('Y-m-d H:i:s') }}">
        <div class="pedido-header" style="display:flex; gap:10px; align-items:flex-start;">
          <i class="material-icons circle red" style="color:white; padding:8px; border-radius:50%;">local_bar</i>

          <div>
            <div style="font-weight:600;">{{ $first->nombre_cliente }} <small class="grey-text">| {{ \Carbon\Carbon::parse($first->creado)->format("H:i")  }}</small></div>
            <div class="grey-text text-darken-1">Ubicación: {{ $first->ubicacion }}</div>

            <ul class="productos">
              @foreach($items as $p)
                <li data-detalle-id="{{ $p->id }}">- {{ $p->producto }} <span class="cantidad">X{{ $p->cantidad_producto }}</span></li>
              @endforeach
            </ul>
          </div>
        </div>
      </li>
    @endforeach
  </ul>
</div>
                    
                        <!-- Completado -->
<div class="col s12 m4" id="completado">
  <h5>Completado</h5>

  <ul class="collection pedidos">
    @foreach(($pedidos['completado'] ?? collect()) as $idConsumo => $items)
        @php 
            $first = $items->first();
            $pedidoKey = $first->pedido_key; 
        @endphp

      <li class="collection-item pedido" data-pedido-key="{{ $pedidoKey }}" data-origen="{{ $first->origen }}"
    data-pedido-id="{{ $first->origen === 'venta_directa' ? $first->venta_directa_id : $first->id_consumo }}"
    data-pedido-creado="{{ \Carbon\Carbon::parse($first->creado)->format('Y-m-d H:i:s') }}">
        <div class="pedido-header" style="display:flex; gap:10px; align-items:flex-start;">
          <i class="material-icons circle red" style="color:white; padding:8px; border-radius:50%;">done_all</i>

          <div>
            <div style="font-weight:600;">{{ $first->nombre_cliente }} <small class="grey-text">| {{ \Carbon\Carbon::parse($first->creado)->format("H:i")  }}</small></div>
            <div class="grey-text text-darken-1">Ubicación: {{ $first->ubicacion }}</div>

            <ul class="productos">
              @foreach($items as $p)
                <li data-detalle-id="{{ $p->id }}">- {{ $p->producto }} <span class="cantidad">X{{ $p->cantidad_producto }}</span></li>
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
    /*
        El flujo del garzon (que confirma "entregado" en /barman/bebidas) aun no esta en uso, asi que nada saca los pedidos de "Completado" en esta pantalla. Para no saturarla: se muestran maximo 3 tarjetas, y si pasan 10 min sin un completado nuevo (en cualquier dispositivo, via el socket), se limpia la zona.
    */
    let timerLimpiezaCompletado = null;

    function recortarCompletado() {
        const lista = document.querySelector('#completado .pedidos');
        if (!lista) return;
        while (lista.children.length > 3) {
            lista.removeChild(lista.firstElementChild);
        }
    }

    function reiniciarTimerLimpiezaCompletado() {
        if (timerLimpiezaCompletado) clearTimeout(timerLimpiezaCompletado);
        timerLimpiezaCompletado = setTimeout(function () {
            const lista = document.querySelector('#completado .pedidos');
            if (lista) lista.innerHTML = '';
        }, 10 * 60 * 1000);
    }

    function registrarLlegadaACompletado() {
        recortarCompletado();
        reiniciarTimerLimpiezaCompletado();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const lista = document.querySelector('#completado .pedidos');
        if (lista && lista.children.length) {
            reiniciarTimerLimpiezaCompletado();
        }
    });
</script>

<script>
    ['por-procesar','en-preparacion','completado'].forEach(function(colId){

            const ul = document.querySelector(`#${colId} .pedidos`);
            if(!ul) return;

            new Sortable(ul, {
                group: 'pedidos',
                animation: 150,
                draggable: '.pedido',
                filter: '.productos, .productos *',
                onEnd: function(evt){

                const pedidoId     = evt.item.getAttribute('data-pedido-id');
                const origen       = evt.item.getAttribute('data-origen') || 'consumo';
                const pedidoCreado = evt.item.getAttribute('data-pedido-creado');
                const nuevoEstado = evt.to.closest('[id]').id;

                fetch(`barman/consumos/${pedidoId}/actualizar-estado`, {
                    method: 'POST',
                    headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ estado: nuevoEstado, pedido_creado: pedidoCreado, origen: origen })
                }).catch(console.error);

                if (nuevoEstado === 'completado') {
                    registrarLlegadaACompletado();
                }
                }
            });
        });
</script>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.Echo !== 'undefined') {
            // Si el socket se reconecta (no la conexion inicial), recargamos para
            // recuperar cualquier evento que se haya perdido mientras estuvo caido.
            let conectadoAntes = false;
            window.Echo.connector.pusher.connection.bind('connected', () => {
                if (conectadoAntes) {
                    location.reload();
                }
                conectadoAntes = true;
            });

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

                    const audio = new Audio('/sounds/notificacionv2.mp3');
                    audio.play();

                // Agregar nuevo consumo a la lista "Por Procesar"
                const listaPorProcesar = document.querySelector('#por-procesar .pedidos');

                // OJO: NuevoConsumoAgregado NO trae e.producto, trae e.productos[]
                // Así que armamos pedidoKey desde el primer producto:
                const first = e.productos && e.productos.length ? e.productos[0] : null;
                if (!first) return;

                // origen distingue si el pedido viene de una reserva (consumo) o de Venta Directa.
                const origen   = first.origen || 'consumo';
                const pedidoId = origen === 'venta_directa' ? first.venta_directa_id : first.id_consumo;
                const pedidoKey = origen === 'venta_directa'
                    ? `vd-${first.venta_directa_id}`
                    : `${first.id_consumo}|${first.pedido_creado}`;

                let pedidoEl = listaPorProcesar.querySelector(`[data-pedido-key="${pedidoKey}"]`);

                if (!pedidoEl) {
                    pedidoEl = document.createElement('li');
                    pedidoEl.className = 'collection-item pedido';
                    pedidoEl.setAttribute('data-pedido-key', pedidoKey);
                    pedidoEl.setAttribute('data-origen', origen);
                    pedidoEl.setAttribute('data-pedido-id', pedidoId);
                    pedidoEl.setAttribute('data-pedido-creado', first.pedido_creado);

                    // pedido_creado viaja como 'Y-m-d H:i:s' desde el backend.
                    const horaPedido = first.pedido_creado ? first.pedido_creado.substring(11, 16) : '';

                    pedidoEl.innerHTML = `
                        <div style="display:flex; gap:10px; align-items:flex-start;">
                        <i class="material-icons circle red" style="color:white; padding:8px; border-radius:50%;">local_drink</i>
                        <div>
                            <div style="font-weight:600;">${first.cliente} <small class="grey-text">| ${horaPedido}</small></div>
                            <div class="grey-text text-darken-1">Ubicación: ${first.ubicacion}</div>
                            <ul class="productos"></ul>
                        </div>
                        </div>
                    `;
                    listaPorProcesar.appendChild(pedidoEl);
                }

                const ulProductos = pedidoEl.querySelector('.productos');

                e.productos.forEach((p) => {
                    // p.id debe ser el id del detalle (detalles_consumos.id)
                    let li = ulProductos.querySelector(`[data-detalle-id="${p.id}"]`);
                    if (!li) {
                        li = document.createElement('li');
                        li.setAttribute('data-detalle-id', p.id);
                        ulProductos.appendChild(li);
                    }
                    li.innerHTML = `- ${p.nombre} <span class="cantidad">X${p.cantidad}</span>`;
                });
            });


            // Escuchar cambios de estado            
            window.Echo.channel('consumo-canal-actualizar')
            .listen('Consumos.EstadoConsumoActualizado', (e) => {
                const audio = new Audio('/sounds/notificacionv2.mp3');
                audio.play();
                
                const nuevoEstado = e.estado;
                const pedidoKey = e.detalleId; // ahora es pedido_key

                const pedidoEl = document.querySelector(`[data-pedido-key="${pedidoKey}"]`);
                if (!pedidoEl) return;

                // Si el garzón lo dejó entregado -> debe desaparecer del barman
                if (nuevoEstado === 'entregado') {
                    pedidoEl.remove();
                    return;
                }

                const nuevaLista = document.querySelector(`#${nuevoEstado} .pedidos`);
                if (nuevaLista) nuevaLista.appendChild(pedidoEl);

                if (nuevoEstado === 'completado') {
                    registrarLlegadaACompletado();
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

                    case 'entregado':
                        estado = 'Pedido entregado al cliente';
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

            // Un producto fue eliminado de un pedido ya creado (edición de venta directa
            // o borrado de un ítem de consumo): quitar solo ese producto de la tarjeta.
            window.Echo.channel('consumo-canal-actualizar')
            .listen('Consumos.ProductoEliminado', (e) => {
                const pedidoEl = document.querySelector(`[data-pedido-key="${e.pedido_key}"]`);
                if (!pedidoEl) return;

                const productoEl = pedidoEl.querySelector(`[data-detalle-id="${e.id_detalle}"]`);
                if (productoEl) productoEl.remove();

                const quedanProductos = pedidoEl.querySelectorAll('.productos > li').length;
                if (quedanProductos === 0) {
                    pedidoEl.remove();
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
                    icon: "warning",
                    title: "Se eliminó un producto del pedido"
                });
            });



        } else {
            console.error("Echo no está definido, verifica la configuración.");
        }

    });
</script>

@endsection