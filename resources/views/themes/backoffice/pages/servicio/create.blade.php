@extends('themes.backoffice.layouts.admin')

@section('title','Crear Servicio')

@section('head')
@endsection

@section('breadcrumbs')
<li>Crear Servicios</li>
@endsection


@section('dropdown_settings')
@endsection

@section('content')

<div class="section">
              <p class="caption">Introduce los datos para crear un nuevo Servicio</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m8 offset-m2 ">
                    <div class="card-panel">
                      <h4 class="header2">Crear Servicio</h4>
                      <div class="row">
                        <form class="col s12" method="post" action="{{route('backoffice.servicio.store')}}">


                        {{csrf_field() }}
                          <div class="row">
                            <div class="input-field col s12 m6 l4">
                              <input id="nombre_servicio" type="text" name="nombre_servicio" value="{{old('nombre_servicio')}}">
                              <label for="nombre_servicio">Nombre del Servicio</label>
                                @error('nombre_servicio')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                  @enderror


                            </div>
                          
                            <div class="input-field col s12 m6 l4">
                              <input id="valor_servicio" type="number" name="valor_servicio" value="{{old('valor_servicio')}}"/>
                              <label for="valor_servicio">Valor Servicio</label>

                              @error('valor_servicio')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                @enderror

                            </div>
                            
                            <div class="input-field col s12 m6 l4">
                              <input id="costo_servicio" type="number" class="materialize-textarea" name="costo_servicio" value="{{old('costo_servicio')}}"/>
                              <label for="costo_servicio">Costo Servicio</label>

                              @error('costo_servicio')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                @enderror

                            </div>

                          </div>
                          <div class="row">

                            <div class="input-field col s12 m6 l4">
                              <input id="duracion" type="text" name="duracion" placeholder="Ingrese minutos. ej. 30 o 120" value="{{old('duracion')}}"/>
                              <label for="duracion">Duración</label>

                              @error('duracion')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                @enderror

                            </div>
                            <div class="row">
                              <div class="input-field col s12">
                                <button class="btn waves-effect waves-light right" type="submit">Guardar
                                  <i class="material-icons right">send</i>
                                </button>
                              </div>
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
