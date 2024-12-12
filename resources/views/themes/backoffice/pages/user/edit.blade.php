@extends('themes.backoffice.layouts.admin')

@section('title','Editar ' . $user->name)

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.user.index') }}">Usuario del sistema</a></li>
<li>Editar {{$user->name }}</li>
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
                        <form class="col s12" method="post" action="{{route('backoffice.user.update', $user)}}">


                        {{ csrf_field() }}
                        {{ method_field('PUT') }}
    
                            

                            <div class="row">
                                <div class="input-field col s12">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $user->name }}" required autocomplete="name" autofocus>
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
                              <input id="dob" type="date" name="dob" value="{{ $user->dob}}">
                                @error('dob')
                                      <span class="invalid-feedback" role="alert">
                                          <strong style="color:red">{{ $message }}</strong>
                                      </span>
                                  @enderror
                            </div>
                          </div>
                         

                          <div class="row">
                            <div class="input-field col s12">
                              <input id="email" type="email" name="email" value="{{ $user->email}}">
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
