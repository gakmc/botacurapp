@extends('themes.backoffice.layouts.admin')

@section('title','Nuestros Clientes')

@section('head')
@endsection


@section('breadcrumbs')
<li><a href="{{route('backoffice.cliente.index')}}">Nuestros Clientes</a></li>
@endsection


@section('dropdown_settings')
<li><a href="{{route ('backoffice.cliente.create') }}" class="grey-text text-darken-2">Crear Cliente</a></li> 
<!-- <li><a href="" class="grey-text text-darken-2">Crear Usuario</a></li> -->
@endsection


@section('content')

<div class="section">
              <p class="caption"><strong>Nuestros Clientes</strong></p>
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
                                    <th>Correo</th>
                                    <th>Whatsapp</th>
                                    <th>Instagram</th>
                                    <th colspan="2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clientes as $cliente )
                                <tr>
                                  <td><a href="{{route('backoffice.cliente.show' ,$cliente )}}">{{$cliente->nombre_cliente}}</a></td>
                                  <td><a href="mailto:{{$cliente->correo}}">{{$cliente->correo}}</a></td>

                                  <td>@if (is_null($cliente->whatsapp_cliente))
                                    No Registra
                                    @else
                                    <a href="https://api.whatsapp.com/send?phone={{$cliente->whatsapp_cliente}}" target="_blank">+{{$cliente->whatsapp_cliente}}</a>
                                  @endif</td>


                                  <td>@if (is_null($cliente->instagram_cliente))
                                    No Registra
                                    @else
                                    <a href="https://www.instagram.com/{{$cliente->instagram_cliente}}" target="_blank">{{$cliente->instagram_cliente}}</a>
                                  @endif</td>
                                  
                                  <td><a href="{{ route('backoffice.cliente.edit', $cliente )}}"><i class="material-icons">mode_edit</i> Editar</a></td>
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


