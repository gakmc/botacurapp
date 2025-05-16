@extends('themes.backoffice.layouts.admin')

@section('title','Ingresar Producto Poro Poro')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{ route('backoffice.poroporo.index') }}">Productos Poro Poro</a></li>
<li>Editar Producto Poro Poro</li>
@endsection



@section('content')

<div class="section">
    <p class="caption">Ingrese los datos para modificar un Producto.</p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2 ">
                <div class="card-panel">
                    <h4 class="header">Modificar Producto <strong>Poro Poro</strong></h4>
                    <div class="row">
                        <form class="col s12" method="post" action="{{route('backoffice.poroporo.update', $poro)}}">


                            {{csrf_field() }}
                            {{method_field('PUT')}}



                            <div class="row">

                                <div class="input-field col s12 m6">

                                    <label for="nombre">Nombre</label>
                                    <input id="nombre" type="text" name="nombre" class="" value="{{ $poro->nombre }}">
                                    @error('nombre')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                <div class="input-field col s12 m6">

                                    <label for="valor">Valor</label>
                                    <input id="valor" type="number" name="valor" class="" value="{{ $poro->valor }}">
                                    @error('valor')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                            </div>
                            
                            <div class="row">
                                

                                <div class="input-field col s12 m6">

                                    <textarea id="descripcion" name="descripcion" class="materialize-textarea">{{$poro->descripcion}}</textarea>
                                    <label for="descripcion">Descripción</label>
                                    @error('descripcion')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                            </div>



                            <div class="row">
<br>
                                <div class="col s12">
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

@endsection