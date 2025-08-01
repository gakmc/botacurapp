@extends('themes.backoffice.layouts.admin')

@section('title', 'Menús')

@section('breadcrumbs')
@endsection

@section('head')
@endsection

@section('content')
<div class="section">
  <p class="caption"><strong>Menús desde <a href="?page=1">{{ now()->format('d-m-Y') }}</a></strong></p>
  <div class="divider"></div>
  <div id="basic-form" class="section">
    <div class="card-panel ">



      @foreach($menusPaginados as $fecha => $reservas)
      <h5>@if (now()->format('d-m-Y') == $fecha)
        Hoy
      @endif
      {{$fecha}}</h5>



      <div id="work-collections">
        <div class="row">

          <div class="col s12 m4 l4">

            @if(isset($entradasPorDia[$fecha]))
            <ul id="projects-collection" class="collection z-depth-1">
              <li class="collection-item avatar">
                <i class="material-icons teal circle">restaurant_menu</i>
                <h6 class="collection-header m-0">Platos de Entrada</h6>
                <p>Total</p>
              </li>
              @foreach($entradasPorDia[$fecha] as $plato => $cantidad)
                <li class="collection-item">
                  <div class="row">
                    <div class="col s9">
                      <p class="collections-title">{{$plato}}:</p>
                    </div>
                    <div class="col s3">
                      <span class="task-cat teal"><strong>{{$cantidad}}</strong>@if ($cantidad <= 1)
                        Plato
                      @else
                        Platos
                      @endif</span>
                    </div>
                  </div>
                </li>
              @endforeach
            </ul>
            @else
            <p>No hay platos para esta fecha.</p>
            @endif
            





          
          </div>


          <div class="col s12 m4 l4">

            @if(isset($fondosPorDia[$fecha]))
            <ul id="projects-collection" class="collection z-depth-1">
              <li class="collection-item avatar">
                <i class="material-icons cyan circle">restaurant</i>
                <h6 class="collection-header m-0">Platos de Fondo</h6>
                <p>Total</p>
              </li>
              @foreach($fondosPorDia[$fecha] as $plato => $cantidad)
                <li class="collection-item">
                  <div class="row">
                    <div class="col s9">
                      <p class="collections-title">{{$plato}}:</p>
                    </div>
                    <div class="col s3">
                      <span class="task-cat cyan"><strong>{{$cantidad}}</strong>@if ($cantidad <= 1)
                        Plato
                      @else
                        Platos
                      @endif</span>
                    </div>
                  </div>
                </li>
              @endforeach
            </ul>
            @else
            <p>No hay platos para esta fecha.</p>
            @endif
            





          
          </div>


          <div class="col s12 m4 l4">

            @if(isset($acompanamientosPorDia[$fecha]))
            <ul id="projects-collection" class="collection z-depth-1">
              <li class="collection-item avatar">
                <i class="material-icons orange circle">restaurant</i>
                <h6 class="collection-header m-0">Acompañamientos</h6>
                <p>Total</p>
              </li>
              @foreach($acompanamientosPorDia[$fecha] as $plato => $cantidad)
              <li class="collection-item">
                <div class="row">
                  <div class="col s9">
                    <p class="collections-title">{{$plato}}:</p>

                  </div>
                  <div class="col s3">
                    <span class="task-cat orange"> <strong>{{ $cantidad }} </strong>@if ($cantidad <= 1)
                      Plato
                    @else
                      Platos
                    @endif</span>
                  </div>
                </div>
              </li>
              @endforeach
            </ul>
            @else
            <p>No hay platos para esta fecha.</p>
            @endif
          </div>



        </div>
      </div>


      @foreach($reservas as $reserva)
      {{-- @foreach($reserva->visitas as $visita) --}}
@if ($reserva->menus->isNotEmpty())
  


      <div class="card-panel">
        <div class="card-content gradient-45deg-light-blue-cyan">
          <h5 class="card-title center white-text">
            
            <i class='material-icons white-text'>restaurant_menu</i> Menús para
            {{ $reserva->cliente->nombre_cliente." - ".$reserva->programa->nombre_programa}} 
            

                <button id="avisar_{{$reserva->id}}" data-id="{{$reserva->id}}" data-url="{{ route('backoffice.reserva.avisar', $reserva->id) }}" class="btn-floating btn-avisar" onclick="darAviso({{$reserva->id}})" @if(in_array($reserva->avisado_en_cocina, [null,'avisado','entregado'])) style="display: none;" @endif>
                    <i class='material-icons'>notifications_active</i>
                </button>

          
          </h5>
        </div>

        <div class="card-content  grey lighten-4">
          <table class="responsive-table">
            <thead class="">
              <tr>
                <th>Menú</th>
                <th>Entrada</th>
                <th>Fondo</th>
                <th>Acompañamiento</th>
                <th>Alérgias</th>
                <th>Observaciones</th>
              </tr>
            </thead>
            <tbody>
              @foreach($reserva->menus as $index => $menu)
              <tr>


                <td>
                  <strong>Menú {{$index + 1}}:</strong>
                </td>
                <td>
                  @if (isset($menu->id_producto_entrada))
                      {{ $menu->productoEntrada->nombre }}
                  @else
                      <span class="red-text">No registra</span>
                  @endif
                </td>
                <td>
                  @if (isset($menu->id_producto_fondo))
                      {{ $menu->productoFondo->nombre }}
                  @else
                      <span class="red-text">No registra</span>
                  @endif
                </td>
                <td>
                  @if ($menu->productoAcompanamiento == null)
                    Sin Acompañamiento
                  @else
                    {{ $menu->productoAcompanamiento->nombre }}
                  @endif
                </td>

                @if ($menu->alergias == null)
                <td> No Registra</td>
                @else

                <td style="color: red">{{ $menu->alergias }}</td>
                @endif

                @if ($menu->observacion == null)
                <td> No Registra</td>
                @else

                <td style="color: red">{{ $menu->observacion }}</td>
                @endif

              </tr>
              @endforeach
              
            </tbody>
          </table>
        </div>
      </div>

      @endif
      {{-- @endforeach --}}
      @endforeach



      @endforeach

      {{-- Paginación --}}
      <div class="center">
        {{ $menusPaginados->links('vendor.pagination.materialize') }}
      </div>



    </div>
  </div>
</div>
@endsection

@section('foot')


<script>
  function darAviso(reservaId) {
  
  
    const elegido = $('#avisar_'+reservaId);
    const url = elegido.data('url');
    
    $.ajax({
      url: url,
      method: 'POST',
      data: {
        _token: '{{csrf_token()}}',
        _method: 'PUT',
        id: reservaId
      },
      success: function(response){
      
        const Toast = Swal.mixin({
          toast:false,
          showConfirmButton: true,
          title: "Se avisó en cocina respecto al menú de "+response.nombreCliente,
          icon: "success",
          showClass: {
            popup: `
            animate__animated
            animate__fadeInUp
            animate__faster
            `
          },
          hideClass: {
            popup: `
            animate__animated
            animate__fadeOutDown
            animate__faster
            `
          },
        });
        
          Toast.fire();
          
          
          
          
          elegido.hide();
      },
      error: function () {
        Swal.fire('Error', 'No se pudo registrar la recepción.', 'error');
      }
    })
  
  }
</script>

<script>
  $(document).ready(function () {
    if (typeof window.Echo !== 'undefined') {
        window.Echo.channel('aviso-cocina')
        .listen('.reservaAvisada', (e) => {
          const boton = $(`#avisar_${e.reservaId}`);
          boton.hide();

        });
    }else{
        console.error('Echo no está inicializado.');
    }
  });
</script>
@endsection