@extends('themes.backoffice.layouts.admin')

@section('title', 'Generar Categoria Masaje')

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
    <p class="caption"><strong>Ingresar Nueva Categoria de Masajes</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2">
                <div class="card-panel">
                    <h4 class="header">Crear Categoria</h4>
                    <div class="row">



                        {{-- CONTENIDO --}}
                        <form action="{{route('backoffice.categoria-masaje.store')}}" method="POST" class="col s12">

                            @csrf

                            <div class="row">
                                <div class="input-field col s12 m8 offset-m2">
                                  <input id="nombre" type="text" name="nombre" value="{{ old('nombre') }}" autofocus>
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