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

    <div class="row">
      <div class="col s12 m8 offset-m2 ">




        <ul class="collapsible popuot">
          <li class="active">
            <div class="collapsible-header active" style="flex-direction:column;">
              <h4>
                
                <i class="material-icons pink-text accent-2" style="font-size: 3rem">style</i>
                <strong>{{$programa->nombre_programa}}</strong>

                <a class="btn-floating btn-small waves-effect waves-light purple right" href="{{route('backoffice.programa.edit', $programa)}}"><i class="material-icons">edit</i></a>
              </h4>
              

              
              
            </div>
            <div class="collapsible-body ">
              
              
              <h5 class="right"><strong>Valor Final: </strong> ${{number_format($programa->valor_programa,0,'','.')}} </h5>
              <h5 class="left"><strong>Valor Programa: </strong> ${{number_format($programa->valor_programa+$programa->descuento,0,'','.')}} </h5>
              <h5 class="center"><strong>Descuento: </strong> ${{number_format($programa->descuento,0,'','.')}} </h5>

              @if ($programa->servicios->isEmpty())
              <h4 class="header2"><strong>No registra servicios asociados al programa.</strong></h4>
              @else
              @foreach ($programa->servicios as $servicio)
              <p>
                <i class="material-icons">beenhere</i> {{$servicio->nombre_servicio}}
              </p>

              @endforeach
              @endif



            </div>
          </li>
        </ul>

      </div>

    </div>






  </div>
</div>
@endsection


@section('foot')
<script>
  $(document).ready(function () {
    $('.collapsible').collapsible();
  });
</script>
@endsection