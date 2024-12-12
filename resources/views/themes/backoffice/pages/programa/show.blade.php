@extends('themes.backoffice.layouts.admin')

@section('title','Programas Botacura')

@section('head')
@endsection

@section('breadcrumbs')
@endsection


@section('dropdown_settings')
@endsection

@section('content')
<div class="section">
  <p class="caption"><strong>Programa: </strong>{{$programa->nombre_programa}}</p>
  <div class="divider"></div>
  <div id="basic-form" class="section">
    {{-- <div class="row">
      <div class="col s12 m8 offset-m2 ">
        <div class="card-panel">
          <h4 class="header2">Programa <strong>{{$programa->nombre_programa}}</strong></h4>
          <div>
            <p><strong>Valor: </strong>{{$programa->valor_programa}}</p>
          </div>
          <div class="row">
            <ul>
              <h4 class="header2">Servicios del programa <strong>{{$programa->nombre_programa}}</strong></h4>
              @foreach ($programa->servicios as $servicio)
              <li>{{$servicio->nombre_servicio}}</li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>
    </div> --}}

    <div class="row">
      <div class="col s12 m8 offset-m2 ">

        <div id="profile-card" class="card" style="overflow: hidden;">
          <div class="card-image waves-effect waves-block waves-light">
            <img class="activator" src="/images/gallary/3.png" alt="Programa bg">
          </div>
          <div class="card-content">
            {{-- class="circle responsive-img activator card-profile-image cyan lighten-1 padding-2" --}}
            <i class="material-icons medium red-text responsive-img activator card-profile-image">redeem</i>

            <a class="btn-floating activator btn-move-up waves-effect waves-light red accent-2 z-depth-4 right">
              <i class="material-icons">remove_red_eye</i>
            </a>
            <span
              class="card-title activator grey-text text-darken-4"><strong>{{$programa->nombre_programa}}</strong></span>
            <p>
              <i class="material-icons">attach_money</i> <strong>{{$programa->valor_programa}}</strong>
            </p>
          </div>
          <div class="card-reveal" style="display: none; transform: translateY(0px);">
            <span class="card-title grey-text text-darken-4"><strong>{{$programa->nombre_programa}}</strong>
              <i class="material-icons right">close</i>
            </span>
            <p>
              <i class="material-icons">room_service</i> <strong>Servicios:</strong>
            </p>
            @if ($programa->servicios->isEmpty())
            <h4 class="header2"><strong>No registra servicios asociados al programa.</strong></h4>
            @else
            @foreach ($programa->servicios as $servicio)
            <p>
              <i class="material-icons">keyboard_arrow_right</i> {{$servicio->nombre_servicio}}
            </p>
            <p>
              @endforeach
              @endif

            <p>
            </p>
          </div>
        </div>

      </div>

    </div>






  </div>
</div>
@endsection


@section('foot')
@endsection