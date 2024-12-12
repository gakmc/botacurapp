@extends('themes.backoffice.layouts.admin')

@section('title','Editar ' . $cliente->nombre_cliente)

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.cliente.index') }}">Nuestros Clientes</a></li>
<li>Editar {{$cliente->nombre_cliente }}</li>
@endsection



@section('content')

<div class="section">
              <p class="caption">Actualiza los datos del usuario</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m8 offset-m2 ">
                    <div class="card-panel">
                      <h4 class="header2">Editar Usuario</h4>
                      <div class="row">
                        <form class="col s12" method="post" action="{{route('backoffice.cliente.update', $cliente)}}">


                        {{ csrf_field() }}
                        {{ method_field('PUT') }}
    
                            

                            <div class="row">
   

                            <div class="input-field col s12 m6">
                              <input id="nombre_cliente" type="text" class="form-control @error('nombre_cliente') is-invalid @enderror" name="nombre_cliente" value="{{ $cliente->nombre_cliente }}" required autocomplete="name" autofocus>
                                  <label for="nombre_cliente">Nombre del cliente</label>

                              @error('nombre_cliente')
                              <span class="invalid-feedback" role="alert">
                                  <strong style="color:red">{{ $message }}</strong>
                              </span>
                          @enderror

                                  

                              </div>

                              <div class="input-field col s12 m6">
                                <input id="correo" type="email" name="correo" value="{{ $cliente->correo }}">
                                <label for="correo">Correo electrónico</label>
                                  @error('correo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color:red">{{ $message }}</strong>
                                        </span>
                                    @enderror
                              </div>

                          </div>     

                       
                        <div class="row">
                          <div class="input-field col s12 m6">
                            <input id="whatsapp_cliente" type="text" name="whatsapp_cliente" class="form-control @error('nombre_cliente') is-invalid @enderror" value="{{  $cliente->whatsapp_cliente }}">
                            <label for="whatsapp_cliente">Whatsapp Cliente</label>
                              @error('whatsapp_cliente')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                @enderror
                          </div>

                          <div class="input-field col s12 m6">
                            <input id="instagram_cliente" type="text" name="instagram_cliente" class="form-control @error('nombre_cliente') is-invalid @enderror" value={{ $cliente->instagram_cliente }}>
                            <label for="instagram_cliente">Instagram Cliente</label>
                              @error('instagram_cliente')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                @enderror
                          </div>

                          
                        </div>
                       

                        <div class="row">
                          <div class="input-field col s12 m6">
                            <select id="sexo" name="sexo">
                              <option value="" disabled selected>-- Seleccione --</option>
                              <option value="Masculino" {{ $cliente->sexo == 'Masculino' ? 'selected' : '' }}>Masculino</option>
                              <option value="Femenino" {{  $cliente->sexo == 'Femenino' ? 'selected' : '' }}>Femenino</option>
                              <option value="na" {{  $cliente->sexo == 'na' ? 'selected' : '' }}>Prefiero no responder</option>
                            </select>
                            <label>Género</label>
                            @error('sexo')
                            <span class="invalid-feedback" role="alert">
                                <strong style="color:red">{{ $message }}</strong>
                            </span>
                        @enderror
                          </div>
                        </div>
                         
                       


                          <div class="row">
                              <div class="input-field col s12">
                                <button class="btn waves-effect waves-light right" type="submit">Actualizar
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
