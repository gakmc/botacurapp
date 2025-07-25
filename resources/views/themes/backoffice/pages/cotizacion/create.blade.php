@extends('themes.backoffice.layouts.admin')

@section('title','Crear Cotización')

@section('head')
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.css') }}">
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.date.css') }}">
<link rel="stylesheet" href="{{ asset('assets/pickadate/lib/themes/default.time.css') }}">
@endsection

@section('breadcrumbs')
@endsection


@section('dropdown_settings')
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Generar Cotización</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">


                        <div class="row">
                            <form class="col s10 offset-s1" method="post" action="{{route('backoffice.cotizacion.store')}}">
                                {{csrf_field()}}

                                <div class="row">
                                    <h4 class="header">Información de cliente</h4>
                                    
                                    <div class="input-field col s12 m6">
                                        <input id="cliente" type="text" class="form-control @error('cliente') is-invalid @enderror" name="cliente" value="{{ old('cliente') }}" autocomplete="name" autofocus>
                                            <label for="cliente">Nombre del cliente</label>

                                        @error('cliente')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                    
                                    
                                    <div class="input-field col s12 m6">
                                        <input id="solicitante" type="text" class="form-control @error('solicitante') is-invalid @enderror" name="solicitante" value="{{ old('solicitante') }}">
                                            <label for="solicitante">Nombre del solicitante</label>

                                        @error('solicitante')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    
                                    <div class="input-field col s12 m6">
                                        <input id="correo" type="email" class="form-control @error('correo') is-invalid @enderror" name="correo" value="{{ old('correo') }}">
                                            <label for="correo">Correo</label>

                                        @error('correo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                    
                                    <div class="input-field col s12 m6">
                                        <input id="validez_dias" type="text" class="form-control @error('validez_dias') is-invalid @enderror" name="validez_dias" value="10" >
                                            <label for="validez_dias">Días de validéz</label>

                                        @error('validez_dias')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                    
                                    <div class="input-field col s12 m6">
                                        <input id="fecha_reserva" type="text" class="form-control @error('fecha_reserva') is-invalid @enderror" name="fecha_reserva" >
                                            <label for="fecha_reserva">Fecha de reserva</label>

                                        @error('fecha_reserva')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>


                                </div>
                                
                                <div class="row">
                                    
                                    <div class="col s12 m6">
                                        <h4 class="header">Seleccione categoria y cantidad del producto</h4>
                                        <div class="row">
                                            <div class="col s6">

                                                <div class="">
                                                    <input class="header-search-input z-depth-2 busca" name="busca"
                                                        placeholder="Buscar..." type="text" />
                                                </div>

                                            </div>
                                        </div>

                                        <h6>Programas disponibles</h6>
                                        <div class="collection lista-programas" id="">
                                            @forelse($programas as $programa)
                                            <a href="#!" class="collection-item programa-item"
                                                data-id="{{ $programa->id }}"
                                                data-nombre="{{ $programa->nombre_programa }}"
                                                data-valor="{{ $programa->valor_programa }}">
                                                {{ $programa->nombre_programa }} - ${{
                                                number_format($programa->valor_programa, 0, ',', '.') }}
                                            </a>
                                            @empty
                                            <a class="collection-item">No existen productos registrados</a>
                                            @endforelse
                                        </div>

                                        <h6>Servicios disponibles</h6>
                                        <div class="collection lista-servicios" id="">
                                            @forelse($servicios as $servicio)
                                            <a href="#!" class="collection-item servicio-item"
                                                data-id="{{ $servicio->id }}"
                                                data-nombre="{{ $servicio->nombre_servicio }}"
                                                data-valor="{{ $servicio->valor_servicio }}">
                                                {{ $servicio->nombre_servicio }} - ${{
                                                number_format($servicio->valor_servicio, 0, ',', '.') }}
                                            </a>
                                            @empty
                                            <a class="collection-item">No existen productos registrados</a>
                                            @endforelse
                                        </div>

                                        {{-- <h6>Productos disponibles</h6>
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
                                        </div> --}}

                                    </div>

                                    <!-- Detalle de la venta -->
                                    <div class="col s12 m6">
                                        <h4 class="header">Detalle Cotización</h4>
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
                                                    <td colspan="2"><input id="total-venta" value="$0"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                <div class="row">
                                    <div class="input-field col s12">
                                        <button class="btn waves-effect waves-light right" type="submit">Generar
                                            <i class="material-icons right">send</i>
                                        </button>
                                    </div>
                                </div>
                                        <div id="productos-form"></div>
                                    </div>
                                </div>




                            </form>

                        </div>

                    </div>

                </div>

            </div>
        </div>
    </div>
</div>
@endsection


@section('foot')

<script src="{{ asset('assets/pickadate/lib/picker.js') }}"></script>
<script src="{{ asset('assets/pickadate/lib/picker.date.js') }}"></script>
<script src="{{ asset('assets/pickadate/lib/picker.time.js') }}"></script>


<script>
  $(document).ready(function () {

    $('#fecha_reserva').pickadate({
      format: 'dd-mm-yyyy',
    })

  });

</script>

<script>
    $(document).ready(function () {
        $('select').material_select();
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

                const inputHidden = document.createElement('input');
                inputHidden.type = 'hidden';
                inputHidden.name = `${tipo}s[${id}][cantidad]`;
                inputHidden.value = 1;
                inputHidden.setAttribute('data-id', `${tipo}_${id}`);
                inputHidden.classList.add('input-cantidad');
                formHidden.appendChild(inputHidden);

                row.querySelector('.cantidad-input').addEventListener('input', function () {
                    const nuevaCantidad = parseInt(this.value) || 1;
                    productosAgregados[`${tipo}_${id}`].cantidad = nuevaCantidad;
                    row.querySelector('.subtotal').textContent = '$' + (valor * nuevaCantidad).toLocaleString();
                    formHidden.querySelector(`input[data-id="${tipo}_${id}"]`).value = nuevaCantidad;
                    actualizarTotal();
                });

                row.querySelector('.eliminar-producto').addEventListener('click', function () {
                    delete productosAgregados[`${tipo}_${id}`];
                    row.remove();
                    formHidden.querySelector(`input[data-id="${tipo}_${id}"]`).remove();
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
            totalVenta.value = '$' + total.toLocaleString();
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