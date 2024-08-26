@extends('themes.backoffice.layouts.admin')

@section('tittle','Crear sector')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.complemento.index') }}">Complementos</a></li>
<li>Crear Sector</li>
@endsection



@section('content')

<div class="section">
              <p class="caption">Introduce los datos para crear un nuevo sector.</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m8 offset-m2 ">
                    <div class="card-panel">
                      <h4 class="header">Crear sector</h4>
                      <div class="row">
                        <form class="col s12" method="post" action="{{route('backoffice.complemento.store')}}">


                        {{csrf_field() }}
 
                            

                            <div class="row">

                                <div class="input-field col s12">
                                  <input id="nombre" type="text" name="nombre" value="{{ old('nombre') }}">
                                  <label for="nombre">Nombre</label>
                                    @error('cantidad_masajes')
                                          <span class="invalid-feedback" role="alert">
                                              <strong style="color:red">{{ $message }}</strong>
                                          </span>
                                      @enderror
                                </div>

                            </div>     

                         
                        
                         

                         


                          <div class="row">
                              <div class="input-field col s12">
                                <button class="btn waves-effect waves-light right" name="table" type="submit" value="sectores">Guardar
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
