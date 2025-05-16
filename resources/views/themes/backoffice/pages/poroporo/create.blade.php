@extends('themes.backoffice.layouts.admin')

@section('title','Ingresar Producto Poro Poro')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{ route('backoffice.poroporo.index') }}">Productos Poro Poro</a></li>
<li>Ingresar Producto Poro Poro</li>
@endsection



@section('content')

<div class="section">
    <p class="caption">Ingrese los datos para Registrar Producto.</p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 m8 offset-m2 ">
                <div class="card-panel">
                    <h4 class="header">Generar <strong>Poro Poro</strong></h4>
                    <div class="row">
                        <form class="col s12" method="post" action="{{route('backoffice.poroporo.store')}}">


                            {{csrf_field() }}



                            <div class="row">

                                <div class="input-field col s12 m6">

                                    <label for="nombre">Nombre</label>
                                    <input id="nombre" type="text" name="nombre" class="" value="{{ old('nombre') }}">
                                    @error('nombre')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                <div class="input-field col s12 m6">

                                    <label for="valor">Valor</label>
                                    <input id="valor" type="number" name="valor" class="" value="{{ number_format(old('valor'),0,'','.') }}">
                                    @error('valor')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                            </div>
                            
                            <div class="row">
                                

                                <div class="input-field col s12 m6">

                                    <textarea id="descripcion" name="descripcion" class="materialize-textarea"></textarea>
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