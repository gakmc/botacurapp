@extends('themes.backoffice.layouts.admin')

@section('title', 'Generar Tipo Masaje')

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
    <p class="caption"><strong>Ingresar Nuevo Tipo de Masajes</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2">
                <div class="card-panel">
                    <h4 class="header">Crear Tipo</h4>
                    <div class="row">



                        {{-- CONTENIDO --}}
                        <form action="{{route('backoffice.tipo-masaje.store')}}" method="POST" class="col s12">

                            @csrf

                            <div class="row">

                                <div class="input-field col s12 m6 l4">
                                  <select name="id_categoria_masaje" id="id_categoria_masaje">
                                      <option selected>-- Seleccione Categoria --</option>
                                      @foreach($categorias as $categoria)
                                      <option value="{{ $categoria->id }}" {{ old('id_categoria_masaje') === $categoria ? 'selected' : '' }}>{{ $categoria->nombre }}
                                      </option>
                                      @endforeach
                                  </select>
                                  @error('id_categoria_masaje')
                                  <span class="invalid-feedback" role="alert">
                                      <strong style="color:red">{{ $message }}</strong>
                                  </span>
                                  @enderror
                                  <label for="id_categoria_masaje">Categoria Masajes</label>
                              </div>

                                <div class="input-field col s12 m6 l4">
                                  <input id="nombre" type="text" name="nombre" value="{{ old('nombre') }}">
                                  <label for="nombre">Nombre</label>
                                    @error('nombre')
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
@endsection