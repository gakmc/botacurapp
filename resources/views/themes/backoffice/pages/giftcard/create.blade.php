@extends('themes.backoffice.layouts.admin')

@section('title', 'Generar nueva Gift Card')

@section('head')
@endsection


@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.cliente.index')}}">Clientes del Sistema</a></li> --}}
{{-- <li>{{$cliente->nombre_cliente}}</li> --}}
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{ route('backoffice.reserva.create',$cliente->id) }}" class="grey-text text-darken-2">Crear Reserva</a></li> --}}
@endsection


@section('content')
<div class="section">
    <p class="caption"><strong>Ingresa los datos para crear una nueva Gift Card</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <h5>Crear Nueva Gift Card</h5>
                    <div class="row">


                        <form class="col s12" method="post" action="{{route('backoffice.giftcards.store')}}">

                            {{csrf_field()}}



                            <div class="row">
                                <div class="input-field col s12 m6 l3">
                                    <input id="de" name="de" type="text" value="{{old('de')}}">
                                    <label for="de">De:</label>
                                    @error('de')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="input-field col s12 m6 l3">
                                    <input id="para" name="para" type="text" value="{{old('para')}}">
                                    <label for="para">Para:</label>
                                    @error('para')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="input-field col s12 m6 l3">
                                    <input id="correo" name="correo" type="text" value="{{old('correo')}}">
                                    <label for="correo">Correo electrónico:</label>
                                    @error('correo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="input-field col s12 m6 l3">
                                    <input id="telefono" name="telefono" type="text" value="{{old('telefono')}}">
                                    <label for="telefono">Teléfono:</label>
                                    @error('telefono')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>


                            <div class="row">
                                <div class="input-field col s12 m6 l3 ">
                                    <select id="id_programa" name="id_programa">
                                        <option value="" disabled selected>-- Seleccione --</option>

                                        @foreach ($programas as $programa)
                                            
                                            <option value="{{$programa->id}}" {{ old('id_programa') == $programa->id ? 'selected' : '' }} data-valor="{{$programa->valor_programa}}">{{$programa->nombre_programa}}</option>

                                        @endforeach


                                    </select>
                                    <label>Programa</label>
                                    @error('id_programa')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                <div class="input-field col s12 m6 l3">
                                    <input type="text" name="cantidad_personas" id="cantidad_personas" value="{{old('cantidad_personas')}}">
                                    <label for="cantidad_personas">Cantidad Personas</label>
                                    @error('cantidad_personas')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color: red;">{{$mesage}}</strong>
                                    </span>
                                    @enderror
                                </div>

                                <div class="input-field col s12 m6 l3">
                                    <input type="text" name="monto" id="monto" value="{{old('monto') ?? 0}}">
                                    <label for="monto">Monto</label>
                                    @error('monto')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color: red;">{{$mesage}}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                         
                        
                         

                         


                          <div class="row">
                              <div class="input-field col s12">
                                <button class="btn waves-effect waves-light right" type="submit">Guardar
                                  <i class="material-icons right">send</i>
                                </button>
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
    
    
    $(document).ready(function () {
        let valor = 0;
        let personas = parseInt($('#cantidad_personas').val()) || 0;

        // Si ya hay un programa seleccionado (old), toma su valor
        let selectedOption = $('#id_programa option:selected');
        if (selectedOption.length > 0 && selectedOption.val() !== "") {
            valor = parseInt(selectedOption.data('valor')) || 0;
        }

        // Si ambos existen al cargar, actualiza el monto
        if (valor > 0 && personas > 0) {
            actualizarMonto(valor, personas);
        }

        // Listeners
        $('#id_programa').on('change', function (e) {
            e.preventDefault();
            valor = parseInt($(this).find('option:selected').data('valor')) || 0;
            actualizarMonto(valor, personas);
        });

        $('#cantidad_personas').on('change keyup', function (e) {
            e.preventDefault();
            personas = parseInt($(this).val()) || 0;
            actualizarMonto(valor, personas);
        });
    });

    function actualizarMonto(valor, personas) {
        $('#monto').val(formatCLP(valor * personas));
    }

    function formatCLP(number) {
        return isNaN(number) ? '$0' : '$' + parseInt(number, 10).toLocaleString('es-CL');
    }

</script>
@endsection