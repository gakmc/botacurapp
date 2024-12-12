@extends('themes.backoffice.layouts.admin')

@section('title','Dar de alta un nuevo usuario')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.user.index') }}">Usuario del sistema</a></li>
<li>Crear Usuario</li>
@endsection



@section('content')

<div class="section">
              <p class="caption">Introduce los datos para crear un nuevo usuario</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m8 offset-m2 ">
                    <div class="card-panel">
                      <h4 class="header2">Crear Usuario</h4>
                      <div class="row">
                        <form class="col s12" method="post" action="{{route('backoffice.user.store')}}">


                        {{csrf_field() }}
                        <div class="row">
                                <div class="input-field col s12">

                                    <select name="role" id="role" required="">

                                        <option value="" disabled="" selected="">-- Selecciona un Rol --</option>
                                    @foreach($roles as $role)
                                        <option value="{{$role->id}}">{{$role->name}}</option>
                                    @endforeach

                                    </select>


                                    @if($errors->has('role_id'))
                                        <!-- <div class="alert alert-danger">{{$messages ?? ''}}</div> -->

                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color: red">{{ $errors->first('role_id') }}</strong>
                                        </span>
                                    @endif


                                </div>
                            </div>     
                            

                            <div class="row">
                                <div class="input-field col s12">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                                    <label for="name">Nombre del Usuario</label>

                                    @if($errors->has('name'))
                                        <!-- <div class="alert alert-danger">{{$messages ?? ''}}</div> -->

                                        <span class="invalid-feedback" role="alert">
                                            <strong style="color: red">{{ $errors->first('name') }}</strong>
                                        </span>

                                    @endif

                                </div>
                            </div>     

                         
                          <div class="row">
                            <div class="input-field col s12">
                              <input id="dob" type="date" name="dob">
                                @error('dob')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                  @enderror
                            </div>
                          </div>
                         

                          <div class="row">
                            <div class="input-field col s12">
                              <input id="email" type="email" name="email">
                              <label for="email">Correo electrónico</label>
                                @error('email')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                  @enderror
                            </div>
                          </div>
                         
                        
                          <div class="row">
                            <div class="input-field col s12">
                              <input id="password" type="password" name="password">
                              <label for="password">Contraseña</label>
                                @error('password')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                  @enderror
                            </div>
                          </div>
                         

                          <div class="row">
                            <div class="input-field col s12">
                              <input id="password_confirmation" type="password" name="password_confirmation">
                              <label for="password_confirmation">Confirmar contraseña</label>
                                @error('password_confirmation')
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
