@extends('themes.backoffice.layouts.admin')

@section('title','Programas Botacura')

@section('head')
@endsection

@section('breadcrumbs')
@endsection


@section('dropdown_settings')
<li><a href="{{route ('backoffice.servicio.create') }}" class="grey-text text-darken-2">Crear Servicio</a></li> 
@endsection

@section('content')
<div class="section">
              <p class="caption"><strong>Programas de Temporada</strong></p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 ">
                    <div class="card-panel">
                     
                      <div class="row">


                      <table>
                            <thead>
                                <tr>
                                    <th>Nombre Servicio	</th>
                                    <th>Valor Servicio</th>
                                    <th>Costo Servicio</th>
                                    <th>Duración</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($servicios as $servicio)
                                <tr>
                                    <td><a href="{{route ('backoffice.servicio.show',$servicio)}}">{{$servicio->nombre_servicio}}</a></td>
                                    <td>{{$servicio->valor_servicio}}</td>
                                    <td>{{$servicio->costo_servicio}}</td>
                                    <td>{{$servicio->duracion}}</td>
                                    <td><a href="{{route ('backoffice.servicio.edit',$servicio)}}">Editar</a></td>
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
