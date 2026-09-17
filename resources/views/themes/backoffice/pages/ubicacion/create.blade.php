@extends('themes.backoffice.layouts.admin')

@section('title','Crear ubicacion')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.complemento.index') }}">Complementos</a></li>
<li>Crear Ubicación</li>
@endsection



@section('content')

<div class="section">
              <p class="caption">Introduce los datos para crear un nuevo ubicacion.</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m8 offset-m2 ">
                    <div class="card-panel">
                      <h4 class="header">Crear ubicacion</h4>
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

                                <div class="input-field col s12 m6">
                                  <select name="espacio_tipo" id="espacio_tipo">
                                    <option value="" {{ old('espacio_tipo') ? '' : 'selected' }}>-- Sin clasificar --</option>
                                    <option value="estacion" {{ old('espacio_tipo') == 'estacion' ? 'selected' : '' }}>Estación</option>
                                    <option value="wellness" {{ old('espacio_tipo') == 'wellness' ? 'selected' : '' }}>Wellness</option>
                                  </select>
                                  <label for="espacio_tipo">Espacio tipo</label>
                                </div>

                                <div class="input-field col s12 m6">
                                  <select name="sub_tipo" id="sub_tipo">
                                    <option value="" {{ old('sub_tipo') ? '' : 'selected' }}>-- Sin clasificar --</option>
                                    <option value="estacion_normal" {{ old('sub_tipo') == 'estacion_normal' ? 'selected' : '' }}>Estación normal (2-3 personas)</option>
                                    <option value="estacion_grupal" {{ old('sub_tipo') == 'estacion_grupal' ? 'selected' : '' }}>Estación grupal (+3 personas)</option>
                                    <option value="terraza" {{ old('sub_tipo') == 'terraza' ? 'selected' : '' }}>Terraza</option>
                                    <option value="reposera" {{ old('sub_tipo') == 'reposera' ? 'selected' : '' }}>Reposera</option>
                                  </select>
                                  <label for="sub_tipo">Sub tipo</label>
                                </div>

                            </div>

                            <div class="row">

                                <div class="input-field col s12 m6">
                                  <input id="capacidad_min" type="number" min="0" name="capacidad_min" value="{{ old('capacidad_min') }}">
                                  <label for="capacidad_min">Capacidad mínima</label>
                                </div>

                                <div class="input-field col s12 m6">
                                  <input id="capacidad_max" type="number" min="0" name="capacidad_max" value="{{ old('capacidad_max') }}">
                                  <label for="capacidad_max">Capacidad máxima</label>
                                </div>

                            </div>

                            <div class="row">

                                <div class="col s12 m6">
                                  <label>
                                    <input type="checkbox" name="tiene_terraza" value="1" {{ old('tiene_terraza') ? 'checked' : '' }}>
                                    <span>Incluye terraza</span>
                                  </label>
                                </div>

                                <div class="col s12 m6">
                                  <label>
                                    <input type="checkbox" name="activo" value="1" {{ old('activo', true) ? 'checked' : '' }}>
                                    <span>Activa (disponible para asignación)</span>
                                  </label>
                                </div>

                            </div>


                          <div class="row">
                              <div class="input-field col s12">
                                <button class="btn waves-effect waves-light right" name="table" type="submit" value="ubicaciones">Guardar
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
