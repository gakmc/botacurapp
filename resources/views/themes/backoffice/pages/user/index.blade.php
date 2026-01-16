@extends('themes.backoffice.layouts.admin')

@section('title','Usuarios del Sistema')

@section('head')
@endsection


@section('breadcrumbs')
<li><a href="{{route('backoffice.user.index')}}">Usuarios del Sistema</a></li>
@endsection


@section('dropdown_settings')
<li><a href="{{route ('backoffice.user.create') }}" class="grey-text text-darken-2">Crear Usuario</a></li> 
<!-- <li><a href="" class="grey-text text-darken-2">Crear Usuario</a></li> -->
@endsection


@section('content')

<div class="section">
              <p class="caption"><strong>Usuarios del Sistema</strong></p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 ">
                    <div class="card-panel">
                     
                      <div class="row">


                      <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Edad</th>
                                    <th>Correo</th>
                                    <th colspan="3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user )
                                <tr>
                                  <td><a href="{{route('backoffice.user.show' ,$user )}}">{{$user->name}}</a></td>
                                  <td>{{$user->age()}}</td>
                                  <td>{{$user->email}}</td>
                                  
                                  <td><a href="{{ route('backoffice.user.edit', $user )}}">Editar</a></td>
                                  @if (Auth::user()->has_role(config('app.admin_role')))
                                    <td><a class="tooltipped" data-position="top" data-delay="50" data-tooltip="Certificado de antiguedad" href="{{ route('backoffice.certificados.antiguedad.create', $user )}}"><i class='material-icons red-text'>library_books</i></a></td>
                                  @endif
                                </tr>
                                @endforeach
                              </tbody>
                            </table>

                      </div>
                    </div>
                  </div>
                </div>
              </div>
</div>
@endsection


@section('foot')
@endsection


