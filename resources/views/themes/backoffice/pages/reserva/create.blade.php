@extends('themes.backoffice.layouts.admin')

@section('title','Crear reserva')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.cliente.show', $cliente->id) }}">Reservas del cliente</a></li>
<li>Crear Reserva</li>
@endsection



@section('content')

<div class="section">
              <p class="caption">Introduce los datos para crear un nuevo reserva</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m8 offset-m2 ">
                    <div class="card-panel">
                      <h4 class="header">Crear reserva para <strong>{{$cliente->nombre_cliente}}</strong></h4>
                      <div class="row">
                        <form class="col s12" method="post" action="{{route('backoffice.reserva.store')}}">


                        {{csrf_field() }}
 
                            

                            <div class="row">
                                <div class="input-field col s12 m6 l4">
                                  
                                  <select name="id_programa" id="id_programa">
                                    <option value="" disabled selected>-- Seleccione un programa --</option>
                                    @foreach ($programas as $programa)
                                    <option value="{{$programa->id}}" {{old('id_programa') == $programa->id ? 'selected' : ''}}>{{$programa->nombre_programa}}</option>
                                    @endforeach
                                  </select>
                                  <label for="id_programa">Cantidad Personas</label>
                                  @error('cantidad_personas')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                @enderror
                                </div>

                                <div class="input-field col s12 m6 l4">
                                <input id="cliente_id" type="hidden" class="form-control" name="cliente_id" value="{{$cliente->id}}" required>


                                    <label for="cantidad_personas">Cantidad Personas</label>

                                    <input id="cantidad_personas" type="number" class="form-control @error('cantidad_personas') is-invalid @enderror" name="cantidad_personas" value="{{old('cantidad_personas')}}" required>
                                    @error('cantidad_personas')
                                    <span class="invalid-feedback" role="alert">
                                        <strong style="color:red">{{ $message }}</strong>
                                    </span>
                                @enderror




                                </div>

                                <div class="input-field col s12 m6 l4">
                                  <input id="cantidad_masajes" type="number" name="cantidad_masajes" value="{{ old('cantidad_masajes') }}">
                                  <label for="cantidad_masajes">Cantidad Masajes</label>
                                    @error('cantidad_masajes')
                                          <span class="invalid-feedback" role="alert">
                                              <strong style="color:red">{{ $message }}</strong>
                                          </span>
                                      @enderror
                                </div>

                            </div>     

                         
                          <div class="row">
                            {{-- <label for="fecha_visita">Fecha Visita</label> --}}
                            <p>Fecha Visita: </p>
                            <div class="input-field col s12 m6">
                              <input id="fecha_visita" type="date" name="fecha_visita" class="" value="{{ old('fecha_visita') }}" placeholder="fecha Visita">
                                @error('fecha_visita')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                  @enderror
                            </div>

                            <div class="input-field col s12 m6">
                              <input id="observacion" name="observacion" type="text" class="" value="{{ old('observacion') }}" />
                              <label for="observacion">Observaciones</label>
                                @error('observacion')
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
@endsection


@section('foot')
@endsection
