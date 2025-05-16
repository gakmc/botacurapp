@extends('themes.backoffice.layouts.admin')

@section('title','Ingresar Venta Poro Poro')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{ route('backoffice.ventas_poroporo.index') }}">Ventas Poro Poro</a></li>
<li>Ingresar Venta Poro Poro</li>
@endsection

@section('content')
<div class="section">
    <p class="caption">Ingrese los datos para registrar una venta.</p>
    <div class="divider"></div>
    <div class="row">
        <div class="col s12 m10 offset-m1">
            <div class="card-panel">
                <h4 class="header">Generar venta <strong>Poro Poro</strong></h4>

                <form method="POST" action="{{ route('backoffice.ventas_poroporo.store') }}">
                    @csrf

                    <div class="row">
                        <!-- Lista de productos -->
                        <div class="col s12 m6">
                            <h6>Productos disponibles</h6>
                            <div class="collection" id="lista-productos">
                                @forelse($productos as $producto)
                                    <a href="#!" class="collection-item producto-item"
                                       data-id="{{ $producto->id }}"
                                       data-nombre="{{ $producto->nombre }}"
                                       data-valor="{{ $producto->valor }}">
                                       {{ $producto->nombre }} - ${{ number_format($producto->valor, 0, ',', '.') }}
                                    </a>
                                @empty
                                    <a class="collection-item">No existen productos registrados</a>
                                @endforelse
                            </div>
                        </div>

                        <!-- Detalle de la venta -->
                        <div class="col s12 m6">
                            <h6>Detalle de venta</h6>
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
                        </div>
                    </div>

                    <!-- Método de pago -->
                    <div class="row">
                        <div class="input-field col s12">
                            <select name="id_tipo_transaccion">
                                <option value="" disabled selected>Seleccione un método de pago</option>
                                @foreach ($tiposTransacciones as $tipo)
                                    <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                @endforeach
                            </select>
                            <label>Método de pago</label>
                            @error('id_tipo_transaccion')
                                <span class="invalid-feedback" role="alert" style="color:red">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <!-- Botón guardar -->
                    <div class="row">
                        <div class="input-field col s12">
                            <button type="submit" class="btn right blue">
                                Guardar <i class="material-icons right">send</i>
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
<script>
    $(document).ready(function () {
        $('select').material_select();
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window._yaCargueListenerProductos) {
        window._yaCargueListenerProductos = true;

        const lista = document.getElementById('lista-productos');
        const tabla = document.getElementById('tabla-detalle');
        const totalVenta = document.getElementById('total-venta');
        const formHidden = document.getElementById('productos-form');
        let productosAgregados = {};

        lista.addEventListener('click', function (e) {
            const item = e.target.closest('.producto-item');
            if (!item) return;

            const id = item.dataset.id;
            if (productosAgregados[id]) {
                console.log("Producto ya agregado:", id);
                return;
            }

            const nombre = item.dataset.nombre;
            const valor = parseInt(item.dataset.valor);

            productosAgregados[id] = { cantidad: 1, valor };

            // Ocultar de la lista original
            item.style.display = 'none';

            const row = document.createElement('tr');
            row.setAttribute('data-id', id);
            row.innerHTML = `
                <td>${nombre}</td>
                <td><input type="number" min="1" value="1" class="cantidad-input" style="width:60px;"></td>
                <td class="subtotal">$${valor.toLocaleString()}</td>
                <td><button type="button" class="btn-small red eliminar-producto"><i class="material-icons">delete</i></button></td>
            `;
            tabla.appendChild(row);

            const inputHidden = document.createElement('input');
            inputHidden.type = 'hidden';
            inputHidden.name = `productos[${id}][cantidad]`;
            inputHidden.value = 1;
            inputHidden.setAttribute('data-id', id);
            inputHidden.classList.add('input-cantidad');
            formHidden.appendChild(inputHidden);

            row.querySelector('.cantidad-input').addEventListener('input', function () {
                const nuevaCantidad = parseInt(this.value) || 1;
                productosAgregados[id].cantidad = nuevaCantidad;
                row.querySelector('.subtotal').textContent = '$' + (valor * nuevaCantidad).toLocaleString();
                formHidden.querySelector(`input[data-id="${id}"]`).value = nuevaCantidad;
                actualizarTotal();
            });

            row.querySelector('.eliminar-producto').addEventListener('click', function () {
                delete productosAgregados[id];
                row.remove();
                formHidden.querySelector(`input[data-id="${id}"]`).remove();
                actualizarTotal();

                // Volver a mostrar en la lista original
                const productoOriginal = document.querySelector(`.producto-item[data-id="${id}"]`);
                if (productoOriginal) {
                    productoOriginal.style.display = 'block';
                }
            });

            actualizarTotal();
        });

        function actualizarTotal() {
            let total = 0;
            for (let id in productosAgregados) {
                total += productosAgregados[id].cantidad * productosAgregados[id].valor;
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
@endsection
