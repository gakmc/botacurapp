@extends('themes.backoffice.layouts.admin')

@section('title', 'Modificar Gift Card')

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
    <p class="caption"><strong>Ingresa los datos a modificar en la Gift Card</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <h5>Modificar Gift Card generada por {{$gc->de}}</h5>
                    <div class="row">


                        <form class="col s12" method="post" action="{{route('backoffice.giftcards.update',$gc)}}">

                            {{csrf_field()}}
                            {{method_field('PUT')}}



                            <div class="row">
                                <div class="input-field col s12 m6 l3">
                                    <input id="de" name="de" type="text" value="{{$gc->de ?? old('de')}}">
                                    <label for="de">De:</label>
                                    @error('de')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="input-field col s12 m6 l3">
                                    <input id="para" name="para" type="text" value="{{$gc->para ?? old('para')}}">
                                    <label for="para">Para:</label>
                                    @error('para')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="input-field col s12 m6 l3">
                                    <input id="correo" name="correo" type="text" value="{{$gc->correo ?? old('correo')}}">
                                    <label for="correo">Correo electrónico:</label>
                                    @error('correo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="input-field col s12 m6 l3">
                                    <input id="telefono" name="telefono" type="text" value="{{$gc->telefono ?? old('telefono')}}">
                                    <label for="telefono">Teléfono:</label>
                                    @error('telefono')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            
                            <div class="row">
                                <div class="input-field col s12 m6 l3">
                                    <input style="cursor: pointer;" type="text" name="codigo" id="codigo" value="{{$gc->codigo ?? old('codigo')}}" readonly>
                                    <label for="codigo">Codigo</label>
                                    @error('codigo')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color: red;">{{$mesage}}</strong>
                                    </span>
                                    @enderror
                                </div>


                                <div class="input-field col s12 m6 l3 ">
                                    <select id="id_programa" name="id_programa">
                                        <option value="" disabled selected>-- Seleccione --</option>

                                        @foreach ($programas as $programa)
                                            
                                            <option value="{{$programa->id}}" {{ ($programa->id == $gc->id_programa) ? 'selected' : old('id_programa') == $programa->id ? 'selected' : '' }} data-valor="{{$programa->valor_programa}}">{{$programa->nombre_programa}}</option>

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
                                    <input type="text" name="cantidad_personas" id="cantidad_personas" value="{{$gc->cantidad_personas ?? old('cantidad_personas')}}">
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
                                <button class="btn waves-effect waves-light right" type="submit">Actualizar
                                  <i class="material-icons right">save</i>
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