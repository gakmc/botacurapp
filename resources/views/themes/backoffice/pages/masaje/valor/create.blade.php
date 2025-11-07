@extends('themes.backoffice.layouts.admin')

@section('title', 'Generar Valor al Tipo Masaje')

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
    <p class="caption"><strong>Ingresar Valores al Tipo de Masajes</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2">
                <div class="card-panel">
                    <h4 class="header">Ingresar Valores por Tipo</h4>
                    <div class="row">



                        {{-- CONTENIDO --}}
                        <form action="{{route('backoffice.masajes.valores.store')}}" method="POST" class="col s12">

                            @csrf

                            <div class="row">

                                <div class="input-field col s12 m6">
                                  <select name="id_tipo_masaje" id="id_tipo_masaje">
                                      <option selected>-- Seleccione Tipo --</option>
                                      @foreach($tipos as $tipo)
                                      <option value="{{ $tipo->id }}" {{ request('id_tipo_masaje') == $tipo->id ? 'selected' : '' }}>{{ $tipo->nombre }}
                                      </option>
                                      @endforeach
                                  </select>
                                  @error('id_tipo_masaje')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                  @enderror
                                  <label for="id_tipo_masaje">Tipo Masaje</label>
                              </div>

                                <div class="input-field col s12 m6">
                                  <input id="duracion_minutos" type="text" name="duracion_minutos" value="{{ old('duracion_minutos') }}">
                                  <label for="duracion_minutos">Duración en Minutos</label>
                                    @error('duracion_minutos')
                                          <span class="invalid-feedback" role="alert">
                                              <strong style="color:red">{{ $message }}</strong>
                                          </span>
                                      @enderror
                                </div>

                            </div>
                            
                            <div class="row">
                                <div class="input-field col s12 m6 l4">
                                  <input id="precio_unitario" type="text" name="precio_unitario" value="{{ old('precio_unitario') ?? '$0'  }}">
                                  <label for="precio_unitario">Precio Unitario</label>
                                    @error('precio_unitario')
                                          <span class="invalid-feedback" role="alert">
                                              <strong style="color:red">{{ $message }}</strong>
                                          </span>
                                      @enderror
                                </div>

                                <div class="input-field col s12 m6 l4">
                                  <input id="precio_pareja" type="text" name="precio_pareja" value="{{ old('precio_pareja') ?? '$0' }}">
                                  <label for="precio_pareja">Precio Pareja</label>
                                    @error('precio_pareja')
                                          <span class="invalid-feedback" role="alert">
                                              <strong style="color:red">{{ $message }}</strong>
                                          </span>
                                      @enderror
                                </div>

                                <div class="input-field col s12 m6 l4">
                                  <input id="pago_masoterapeuta" type="text" name="pago_masoterapeuta" value="{{ old('pago_masoterapeuta') ?? '$0' }}">
                                  <label for="pago_masoterapeuta">Valor Masoterapeuta</label>
                                    @error('pago_masoterapeuta')
                                          <span class="invalid-feedback" role="alert">
                                              <strong style="color:red">{{ $message }}</strong>
                                          </span>
                                      @enderror
                                </div>
                                
                            </div>
                            

                            <div class="col s12 right-align">
                                <button type="submit" class="btn waves-effect waves-light">
                                    Guardar
                                    <i class="material-icons right">send</i>
                                </button>
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
  function formatCLP(valor) {
    return '$' + valor.toLocaleString('es-CL');
  }

  function limpiarNumero(valor) {
    return parseInt(valor.replace(/[$.]/g, '')) || 0;
  }

  $('#precio_unitario').change(function (e) {
    var precio_unitario = limpiarNumero($('#precio_unitario').val());
    $('#precio_unitario').val(formatCLP(precio_unitario));
  });

  $('#precio_pareja').change(function (e) {
    var precio_pareja = limpiarNumero($('#precio_pareja').val());
    $('#precio_pareja').val(formatCLP(precio_pareja));
  });

  $('#pago_masoterapeuta').change(function (e) {
    var pago_masoterapeuta = limpiarNumero($('#pago_masoterapeuta').val());
    $('#pago_masoterapeuta').val(formatCLP(pago_masoterapeuta));
  });
});
</script>
@endsection