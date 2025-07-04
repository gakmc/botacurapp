@extends('themes.backoffice.layouts.admin')

@section('title','Crear Categoria de Compra')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.complemento.index') }}">Complementos</a></li>
<li>Crear Sub Categoria</li>
@endsection



@section('content')

<div class="section">
              <p class="caption">Introduce los datos para crear una nueva categoria.</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m8 offset-m2 ">
                    <div class="card-panel">
                      <h4 class="header">Crear Sub categoria</h4>
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
                                  <select name="categoria_id" id="categoria_id">
                                    <option value="" disabled selected>-- Seleccione --</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{$categoria->id}}">{{$categoria->nombre}}</option>
                                    @endforeach
                                  </select>
                                  <label for="categoria_id">categoria_id</label>
                                    @error('categoria_id')
                                          <span class="invalid-feedback" role="alert">
                                              <strong style="color:red">{{ $message }}</strong>
                                          </span>
                                      @enderror
                                </div>

                            </div>



                         
                        
                         

                         


                          <div class="row">
                              <div class="input-field col s12">
                                <button class="btn waves-effect waves-light right" name="table" type="submit" value="subcategoria">Guardar
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
<script>
    $(document).ready(function () {
        $('select').material_select();
    });
</script>
@endsection
