@extends('themes.backoffice.layouts.admin')

@section('title','Modificar Cotización')

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
@php
    $seleccionados = collect($cotizacion->items)->map(function ($i) {
        return strtolower(class_basename($i->itemable_type)) . '_' . $i->itemable->id;
    })->toArray();
@endphp
<div class="section">
    <p class="caption"><strong>Actualizar Cotización</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">


                        <div class="row">
                            <form class="col s10 offset-s1" method="post"
                                action="{{route('backoffice.cotizacion.update', $cotizacion)}}">
                                {{csrf_field()}}
                                @method('PUT')

                                <div class="row">
                                    <h4 class="header">Información de cliente</h4>

                                    <div class="input-field col s12 m6">
                                        <input id="cliente" type="text"
                                            class="form-control @error('cliente') is-invalid @enderror" name="cliente"
                                            value="{{ old('cliente', $cotizacion->cliente)}}">
                                        <label for="cliente">Nombre del cliente</label>

                                        @error('cliente')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>


                                    <div class="input-field col s12 m6">
                                        <input id="solicitante" type="text"
                                            class="form-control @error('solicitante') is-invalid @enderror"
                                            name="solicitante"
                                            value="{{ old('solicitante', $cotizacion->solicitante) }}">
                                        <label for="solicitante">Nombre del solicitante</label>

                                        @error('solicitante')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>


                                    <div class="input-field col s12 m6">
                                        <input id="correo" type="email"
                                            class="form-control @error('correo') is-invalid @enderror" name="correo"
                                            value="{{ old('correo', $cotizacion->correo) }}">
                                        <label for="correo">Correo</label>

                                        @error('correo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="input-field col s12 m6">
                                        <input id="validez_dias" type="text"
                                            class="form-control @error('validez_dias') is-invalid @enderror"
                                            name="validez_dias" value="{{$cotizacion->validez_dias}}">
                                        <label for="validez_dias">Días de validéz</label>

                                        @error('validez_dias')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                    <div class="input-field col s12 m6">
                                        <input id="fecha_reserva" type="text"
                                            class="form-control @error('fecha_reserva') is-invalid @enderror"
                                            name="fecha_reserva"
                                            value="{{old('fecha_reserva', Carbon\Carbon::parse($cotizacion->fecha_reserva)->format('d-m-Y'))}}">
                                        <label for="fecha_reserva">Fecha de reserva</label>

                                        @error('fecha_reserva')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>


                                </div>



                                <h4 class="header">Seleccione Categoria y cantidad del producto</h4>

                                <div class="row">
                                    <div class="col s3">
                                        <input class="busca" placeholder="Buscar..." type="text">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col s12 m6">
                                        <h6>Programas</h6>
                                        <div class="collection lista-programas">
                                            @foreach($programas as $programa)
                                            @php $key = 'programa_'.$programa->id; @endphp
                                            <a href="#!"
                                                class="collection-item programa-item {{ in_array($key, $seleccionados) ? 'agregado' : '' }}"
                                                data-id="{{ $programa->id }}"
                                                data-nombre="{{ $programa->nombre_programa }}"
                                                data-valor="{{ $programa->valor_programa }}"
                                                style="{{ in_array($key, $seleccionados) ? 'display:none;' : '' }}">
                                                {{ $programa->nombre_programa }} - ${{
                                                number_format($programa->valor_programa, 0, ',', '.') }}
                                            </a>
                                            @endforeach
                                        </div>

                                        <h6>Servicios</h6>
                                        <div class="collection lista-servicios">
                                            @foreach($servicios as $servicio)
                                            @php $key = 'servicio_'.$servicio->id; @endphp
                                            <a href="#!"
                                                class="collection-item servicio-item {{ in_array($key, $seleccionados) ? 'agregado' : '' }}"
                                                data-id="{{ $servicio->id }}"
                                                data-nombre="{{ $servicio->nombre_servicio }}"
                                                data-valor="{{ $servicio->valor_servicio }}"
                                                style="{{ in_array($key, $seleccionados) ? 'display:none;' : '' }}">
                                                {{ $servicio->nombre_servicio }} - ${{
                                                number_format($servicio->valor_servicio, 0, ',', '.') }}
                                            </a>
                                            @endforeach
                                        </div>

                                        {{-- <h6>Productos</h6>
                                        <div class="collection lista-productos">
                                            @foreach($productos as $producto)
                                            @php $key = 'producto_'.$producto->id; @endphp
                                            <a href="#!"
                                                class="collection-item producto-item {{ in_array($key, $seleccionados) ? 'agregado' : '' }}"
                                                data-id="{{ $producto->id }}" data-nombre="{{ $producto->nombre }}"
                                                data-valor="{{ $producto->valor }}"
                                                style="{{ in_array($key, $seleccionados) ? 'display:none;' : '' }}">
                                                {{ $producto->nombre }} - ${{ number_format($producto->valor, 0, ',',
                                                '.') }}
                                            </a>
                                            @endforeach
                                        </div> --}}
                                    </div>

                                    <div class="col s12 m6">
                                        <h6>Detalle</h6>
                                        <table class="highlight">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Cantidad</th>
                                                    <th>Subtotal</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="tabla-detalle">
                                                @foreach($cotizacion->items as $item)
                                                @php
                                                $tipo = strtolower(class_basename($item->itemable_type));
                                                $id = $item->itemable->id;
                                                $nombre = $item->itemable->nombre ?? $item->itemable->nombre_programa ??
                                                $item->itemable->nombre_servicio;
                                                $valor = $item->valor_neto;
                                                $cantidad = $item->cantidad;
                                                @endphp
                                                <tr data-id="{{ $tipo }}_{{ $id }}">
                                                    <td>{{ $nombre }}</td>
                                                    <td><input type="number" value="{{ $cantidad }}"
                                                            class="cantidad-input" style="width:60px;"></td>
                                                    <td class="subtotal">${{ number_format($valor * $cantidad, 0, ',',
                                                        '.') }}</td>
                                                    <td><button type="button" class="btn-small red eliminar-producto"><i
                                                                class="material-icons">delete</i></button></td>
                                                </tr>
                                                <input type="hidden" name="{{ $tipo }}s[{{ $id }}][cantidad]"
                                                    value="{{ $cantidad }}" data-id="{{ $tipo }}_{{ $id }}"
                                                    class="input-cantidad">
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="2" class="right-align"><strong>Total:</strong></td>
                                                    <td colspan="2"><strong id="total-venta">$0</strong></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                <div class="row">
                                    <div class="input-field col s12">
                                        <button class="btn waves-effect waves-light right" type="submit">Actualizar
                                            <i class="material-icons right">save</i>
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

            document.querySelectorAll('#tabla-detalle tr[data-id]').forEach(row => {
                const id = row.getAttribute('data-id');
                const tipo = id.split('_')[0];
                const input = row.querySelector('.cantidad-input');
                const valor = parseInt(
                    document.querySelector(`.${tipo}-item[data-id="${id.split('_')[1]}"]`)?.dataset.valor || 0
                );
                const cantidad = parseInt(input.value) || 1;

                productosAgregados[id] = { cantidad: cantidad, valor: valor };

                // Agregar listeners a los precargados
                input.addEventListener('input', function () {
                    const nuevaCantidad = parseInt(this.value) || 1;
                    productosAgregados[id].cantidad = nuevaCantidad;
                    row.querySelector('.subtotal').textContent = '$' + (valor * nuevaCantidad).toLocaleString();
                    formHidden.querySelector(`input[data-id="${id}"]`).value = nuevaCantidad;
                    actualizarTotal();
                });

                row.querySelector('.eliminar-producto').addEventListener('click', function () {
                    delete productosAgregados[id];
                    row.remove();
                    const hiddenInput = document.querySelector(`input.input-cantidad[data-id="${id}"]`);
                    if (hiddenInput) hiddenInput.remove();
                    actualizarTotal();

                    // Mostrar de nuevo en la lista original
                    const original = document.querySelector(`.${tipo}-item[data-id="${id.split('_')[1]}"]`);
                    if (original) {
                        original.classList.remove('agregado');
                        original.style.display = 'block';
                    }
                });
            });


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
                        const hiddenInput = document.querySelector(`input.input-cantidad[data-id="${id}"]`);
                        if (hiddenInput) hiddenInput.remove();
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

        actualizarTotal();

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