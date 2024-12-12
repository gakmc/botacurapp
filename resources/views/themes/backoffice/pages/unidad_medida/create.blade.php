@extends('themes.backoffice.layouts.admin')

@section('title','Crear unidad de medida')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.complemento.index') }}">Complementos</a></li>
<li>Crear Unidad de Medida</li>
@endsection



@section('content')

<div class="section">
              <p class="caption">Introduce los datos para crear una nueva unidad de medida.</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m8 offset-m2 ">
                    <div class="card-panel">
                      <h4 class="header">Crear unidad de medida</h4>
                      <div class="row">
                        <form class="col s12" method="post" action="{{route('backoffice.complemento.store')}}">


                        {{csrf_field() }}
 
                            

                            <div class="row">

                                <div class="input-field col s12 m6">
                                  <input id="nombre" type="text" name="nombre" value="{{ old('nombre') }}" autofocus>
                                  <label for="nombre">Nombre</label>
                                    @error('nombre')
                                          <span class="invalid-feedback" role="alert">
                                              <strong style="color:red">{{ $message }}</strong>
                                          </span>
                                      @enderror
                                </div>

                                <div class="input-field col s12 m6">
                                  <input id="abreviatura" type="text" name="abreviatura" value="{{ old('abreviatura') }}" placeholder="Ingrese sin puntos">
                                  <label for="abreviatura">Abreviatura</label>
                                    @error('abreviatura')
                                          <span class="invalid-feedback" role="alert">
                                              <strong style="color:red">{{ $message }}</strong>
                                          </span>
                                      @enderror
                                </div>

                            </div>

                            <div class="row">

                                <div class="input-field col s12 m6">
                                    <select id="tipo" name="tipo">
                                        <option value="" disabled selected>-- Seleccione --</option>
                                        <option value="peso" {{ old('tipo') == 'peso' ? 'selected' : '' }}>Peso</option>
                                        <option value="volumen" {{ old('tipo') == 'volumen' ? 'selected' : '' }}>Volumen</option>
                                        <option value="longitud" {{ old('tipo') == 'longitud' ? 'selected' : '' }}>Longitud</option>
                                        <option value="otro" {{ old('tipo') == 'otro' ? 'selected' : '' }}>Otro</option>
                                      </select>
                                      <label>Tipo</label>
                                      @error('sexo')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                  @enderror
                                </div>

                                <div class="input-field col s12 m6">
                                    <textarea id="description" class="materialize-textarea" name="description"></textarea>
                                    <label for="description">Descripción</label>

                                  {{-- <input id="descripcion" type="text" name="descripcion" value="{{ old('descripcion') }}"> --}}
                                  {{-- <label for="descripcion">Descripción</label> --}}
                                    @error('descripcion')
                                          <span class="invalid-feedback" role="alert">
                                              <strong style="color:red">{{ $message }}</strong>
                                          </span>
                                      @enderror
                                      
                                </div>

                            </div>     

                         
                        
                         

                         


                          <div class="row">
                              <div class="input-field col s12">
                                <button class="btn waves-effect waves-light right" name="table" type="submit" value="unidades_medidas">Guardar
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
