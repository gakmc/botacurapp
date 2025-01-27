@extends('themes.backoffice.layouts.admin')

@section('title','Programas Botacura')

@section('head')
@endsection

@section('breadcrumbs')
@endsection


@section('dropdown_settings')
<li><a href="{{route ('backoffice.programa.create') }}" class="grey-text text-darken-2">Crear Programa</a></li> 
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
                                    <th>Nombre Programa	</th>
                                    <th>Valor Programa</th>
                                    <th>Descuento</th>
                                    <th>Valor Final</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($programa as $programa)
                                <tr>
                                    <td><a href="{{route ('backoffice.programa.show',$programa)}}">{{$programa->nombre_programa}}</a></td>
                                    <td>${{number_format($programa->valor_programa + $programa->descuento,0,'','.')}}</td>
                                    <td>${{number_format($programa->descuento,0,'','.')}}</td>
                                    <td><strong>${{number_format($programa->valor_programa,0,'','.')}}</strong></td>
                                    <td><a href="{{route ('backoffice.programa.edit',$programa)}}">Editar</a></td>
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
