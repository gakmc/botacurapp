@extends('themes.backoffice.layouts.admin')

@section('title','Editar Categoria de Compra')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.complemento.index') }}">Complementos</a></li>
<li>Editar Sub Categoria</li>
@endsection



@section('content')

<div class="section">
              <p class="caption">Introduce los datos para editar subcategoria {{$subcategoria->nombre}}.</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m8 offset-m2 ">
                    <div class="card-panel">
                      <h4 class="header">Editar subcategoria</h4>
                      <div class="row">
                        <form class="col s12" method="post" action="{{route('backoffice.complemento.update', $subcategoria)}}">


                        {{csrf_field() }}
                        {{  method_field('PUT') }}
 
                            

                            <div class="row">

                                <div class="input-field col s12 m6">
                                  <input id="nombre" type="text" name="nombre" value="{{ $subcategoria->nombre }}" autofocus>
                                  <label for="nombre">Nombre</label>
                                    @error('nombre')
                                          <span class="invalid-feedback" role="alert">
                                              <strong style="color:red">{{ $message }}</strong>
                                          </span>
                                      @enderror
                                </div>


                                <div class="input-field col s12 m6">
                                  <select name="categoria_id" id="categoria_id">
                                    <option value="" disabled>-- Seleccione --</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{$categoria->id}}" @if ($subcategoria->categoria_id == $categoria->id) selected @endif>{{$categoria->nombre}}</option>
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
                                <button class="btn waves-effect waves-light right" name="table" type="submit" value="subcategoria">Actualizar
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
