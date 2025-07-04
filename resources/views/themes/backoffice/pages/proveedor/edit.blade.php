@extends('themes.backoffice.layouts.admin')

@section('title', 'Modificar Proveedor')

@section('head')
@endsection


@section('breadcrumbs')
<li><a href="{{route('backoffice.egreso.index')}}">Egresos</a></li>
<li><a href="{{route('backoffice.proveedor.index')}}">Lista de Proveedores</a></li>
<li>Modificar Proveedor</li>
@endsection

@section('dropdown_settings')
{{-- <li><a href="{{ route('backoffice.reserva.create',$cliente->id) }}" class="grey-text text-darken-2">Crear Reserva</a></li> --}}
@endsection


@section('content')
<div class="section">
    <p class="caption"><strong>Actualizar proveedor</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">

            <div class="col s12 m8 offset-m1">
                
                    <h4 class="header">Ingrese los datos para modificar <strong>{{$proveedor->nombre}}</strong></h4>
                    
                        <form class="col s12" method="post"
                            action="{{route('backoffice.proveedor.update', $proveedor)}}">
                            {{csrf_field()}}
                            {{method_field('PUT')}}

                        <div class="row">
                            <div class="input-field col s12 m6">
                                <input type="text" name="rut" value="{{$proveedor->rut}}">
                                <label for="rut">Rut</label>
                                @error('rut')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>


                            <div class="input-field col s12 m6">
                                <input type="text" name="nombre" value="{{$proveedor->nombre}}">
                                <label for="nombre">Nombre</label>
                                @error('nombre')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="input-field col s12 m6">
                                <input type="text" name="telefono" value="{{$proveedor->telefono}}">
                                <label for="telefono">Teléfono</label>
                                @error('telefono')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>


                            <div class="input-field col s12 m6">
                                <input type="email" name="correo" value="{{$proveedor->correo}}">
                                <label for="correo">Correo Electrónico</label>
                                @error('correo')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
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
</div>
@endsection

@section('foot')
@endsection