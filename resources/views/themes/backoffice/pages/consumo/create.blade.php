@extends('themes.backoffice.layouts.admin')

@section('title', 'Ingresar Consumo')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.show', $venta->id_reserva) }}">Consumo para reserva del cliente</a></li>
<li>Ingresar Consumo</li>
@endsection

@section('content')

<div class="section">
    <p class="caption">Ingrese los datos del consumo para la venta.</p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2">
                <div class="card-panel">
                    <h4 class="header">Consumo para la venta de
                        <strong>{{$venta->reserva->cliente->nombre_cliente}}</strong>
                    </h4>
                    <div class="row">
                        <form class="col s12" method="post"
                            action="{{route('backoffice.venta.consumo.store', $venta)}}">
                            {{csrf_field()}}



                            <div class="row">
                                <div class="input-field col s12 m6 l4" hidden>
                                    <input id="id_venta" type="hidden" name="id_venta" value="{{$venta->id}}" required>
                                </div>

                                <div class="row">
                                    
                                    <div class="col s12 m6">
                                        <h6>SELECCIONE PRODUCTO Y CANTIDAD DEL PRODUCTO</h6>
                                        <div class="row">
                                            <div class="col s6">

                                                <div class="">
                                                    <input class="header-search-input z-depth-2 busca" name="busca"
                                                        placeholder="Buscar..." type="text" />
                                                </div>

                                            </div>
                                        </div>


                                        <h6>Productos disponibles</h6>
                                        <div class="collection lista-productos" id="">
                                            @forelse($productos as $producto)
                                            <a href="#!" class="collection-item producto-item"
                                                data-id="{{ $producto->id }}" data-nombre="{{ $producto->nombre }}"
                                                data-valor="{{ $producto->valor }}">
                                                {{ $producto->nombre }} - ${{ number_format($producto->valor, 0, ',',
                                                '.') }}
                                            </a>
                                            @empty
                                            <a class="collection-item">No existen productos registrados</a>
                                            @endforelse
                                        </div>

                                    </div>

                                    <!-- Detalle de la venta -->
                                    <div class="col s12 m6">
                                        <h4 class="header">Detalle Consumo</h4>
                                        <table class="highlight">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Cantidad</th>
                                                    <th>Subtotal</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="tabla-detalle"></tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="2" class="right-align"><strong>Total:</strong></td>
                                                    <td colspan="2"><strong id="total-venta">$0</strong></td>
                                                </tr>
                                            </tfoot>
                                        </table>

                                        <div id="productos-form"></div>
                                                                                                <div class="row">
                                <div class="input-field col s12">
                                    <button id="btn-guardar" class="btn waves-effect waves-light right" type="submit">Guardar
                                        <i class="material-icons right">send</i>
                                    </button>
                                </div>
                            </div>
                                    </div>
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
    $(document).ready(function(){
        $('.tabs').tabs();
    });

    $(document).ready(function () {
        // let nombreTipo = $('#nombreSeleccion').text();
        let seleccion = $('#seleccion').text();
        
        
            // Obtener el texto del primer tab visible y mostrarlo en el h5 al cargar la página
            let nombreInicial = $('.tabs .tab a.active').text();
            if (!nombreInicial) {
                nombreInicial = $('.tabs .tab a:first').text();
            }
            $('#nombreSeleccion').text(nombreInicial); // Mostrar el nombre inicial

            // Escuchar el evento click en las pestañas
            $('.tabs .tab a').on('click', function (e) {
                e.preventDefault();

                // Obtener el texto del enlace seleccionado
                let nombreTipo = $(this).text();

                // Actualizar el contenido del h5 con id "nombreSeleccion"
                $('#nombreSeleccion').text(nombreTipo);
            });
    });
</script>


<script>
  $(document).ready(function () {
    $('form').on('submit', function (){
      const $btn = $('#btn-guardar');
      $btn.prop('disabled', true);
      $btn.html('<i class="material-icons left">hourglass_empty</i>Guardando...');
    });
  });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    if (!window._yaCargueListenerProductos) {
        window._yaCargueListenerProductos = true;

        const tabla = document.getElementById('tabla-detalle');
        const totalVenta = document.getElementById('total-venta');
        const formHidden = document.getElementById('productos-form');
        let productosAgregados = {};

        document.querySelectorAll('.collection-item').forEach(item => {
            item.addEventListener('click', function () {
                const tipo = this.classList.contains('producto-item') ? 'producto' :
                             this.classList.contains('programa-item') ? 'programa' :
                             this.classList.contains('servicio-item') ? 'servicio' : null;

                if (!tipo) return;

                const id = this.dataset.id;
                if (productosAgregados[`${tipo}_${id}`]) {
                    console.log(`${tipo} ya agregado:`, id);
                    return;
                }

                const nombre = this.dataset.nombre;
                const valor = parseInt(this.dataset.valor);

                productosAgregados[`${tipo}_${id}`] = { cantidad: 1, valor };

                // Ocultar de la lista original
                this.classList.add('agregado');
                this.style.display = 'none';


                const row = document.createElement('tr');
                row.setAttribute('data-id', `${tipo}_${id}`);
                row.innerHTML = `
                    <td>${nombre}</td>
                    <td><input type="number" min="1" value="1" class="cantidad-input" style="width:60px;"></td>
                    <td class="subtotal">$${valor.toLocaleString()}</td>
                    <td><button type="button" class="btn-small red eliminar-producto"><i class="material-icons">delete</i></button></td>
                `;
                tabla.appendChild(row);

                // Input de cantidad
                const inputCantidad = document.createElement('input');
                inputCantidad.type = 'hidden';
                inputCantidad.name = `productos[${id}][cantidad]`;
                inputCantidad.value = 1;
                inputCantidad.setAttribute('data-id', `cantidad_${id}`);
                formHidden.appendChild(inputCantidad);

                // Input de valor
                const inputValor = document.createElement('input');
                inputValor.type = 'hidden';
                inputValor.name = `productos[${id}][valor]`;
                inputValor.value = valor;
                inputValor.setAttribute('data-id', `valor_${id}`);
                formHidden.appendChild(inputValor);


                row.querySelector('.cantidad-input').addEventListener('input', function () {
                    const nuevaCantidad = parseInt(this.value) || 1;
                    productosAgregados[`${tipo}_${id}`].cantidad = nuevaCantidad;
                    row.querySelector('.subtotal').textContent = '$' + (valor * nuevaCantidad).toLocaleString();
                    formHidden.querySelector(`input[data-id="cantidad_${id}"]`).value = nuevaCantidad;

                    actualizarTotal();
                });

                row.querySelector('.eliminar-producto').addEventListener('click', function () {
                    delete productosAgregados[`${tipo}_${id}`];
                    row.remove();
                    formHidden.querySelector(`input[data-id="cantidad_${id}"]`)?.remove();
                    formHidden.querySelector(`input[data-id="valor_${id}"]`)?.remove();

                    actualizarTotal();

                    // Volver a mostrar en la lista original
                    const original = document.querySelector(`.${tipo}-item[data-id="${id}"]`);
                    if (original) {
                        original.classList.remove('agregado');
                        original.style.display = 'block';
                    }
                });

                actualizarTotal();
            });
        });

        function actualizarTotal() {
            let total = 0;
            for (let key in productosAgregados) {
                total += productosAgregados[key].cantidad * productosAgregados[key].valor;
            }
            totalVenta.textContent = '$' + total.toLocaleString();
        }
    }
});
</script>


<script>
    @if ($errors->any())
        @foreach ($errors->all() as $error)
            Swal.fire({
                toast: true,
                position: 'center',
                icon: 'error',
                title: '{{ $error }}',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true
            });
        @endforeach
    @endif
</script>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const buscador = document.querySelector('.busca');

        if (buscador) {
            buscador.addEventListener('input', function () {
                const texto = this.value.trim().toLowerCase();

                document.querySelectorAll('.collection-item').forEach(item => {
                    const nombre = item.dataset.nombre?.toLowerCase() || '';
                    
                    const yaAgregado = item.classList.contains('agregado');

                    if (texto === '') {
                        if (!yaAgregado) item.style.display = 'block';
                    } else {
                        if (!yaAgregado && nombre.includes(texto)) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    }
                });
            });
        }
    });
</script>



@endsection