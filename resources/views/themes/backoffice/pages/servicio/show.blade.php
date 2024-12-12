@extends('themes.backoffice.layouts.admin')

@section('title','Servicios Botacura')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.servicio.index')}}">Servicios</a></li>
<li>{{$servicio->nombre_servicio}}</li>
@endsection


@section('dropdown_settings')
@endsection

@section('content')
<div class="section">
              <p class="caption"><strong>Servicio: </strong>{{$servicio->nombre_servicio}}</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m8 offset-m2 ">
                    <div class="card-panel">
                      <h4 class="header2">Servicio <strong>{{$servicio->nombre_servicio}}</strong></h4>
                      <div>
                        <p><strong>Valor: </strong>{{$servicio->valor_servicio}}</p>
                        <p><strong>Costo: </strong>{{$servicio->costo_servicio}}</p>
                        <p><strong>Duracion: </strong>{{$servicio->duracion}} minutos</p>
                    </div>
                      <div class="row">
                        <ul>

                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
</div>
@endsection


@section('foot')
@endsection
