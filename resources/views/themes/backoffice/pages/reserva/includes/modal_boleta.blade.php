<!-- Modal Structure -->
<div id="modal-{{$reserva->id}}" class="modal modal-fixed-footer">

          <div class="modal-content">
            <div id="boleta-contenido">
              <div class="row">
                <div class="col s12 center">
                  <img src="{{asset('images\logo\logo.png')}}" alt="logo">
                  <h5 class="col s12 center">Centro Recreativo Botacura LTDA.</h5>
                  <h6 class="col s12 center"><strong>Rut: </strong>77.848.621-0</h6>
                  <h6 class="col s12 center"><strong>Dirección: </strong>Cam. Al Volcán 13274, El Manzano, San José de Maipo, Región Metropolitana</h6>
                </div>
              </div>
      
              <div class="row">
                <div class="col s12">
                  <p class="col s6 left"><strong>Generada por:</strong> <span>{{ Auth::user()->name }}</span></p>
                  <p class="col s6 right"><strong>Fecha:</strong> <span>{{ now()->format('d-m-Y') }}</span></p>
                </div>
              </div>
      
              <div class="row">
                <div class="col s12">
                  <p class="col s6 left"><strong>Cliente:</strong> <span> {{$reserva->cliente->nombre_cliente}} </span></p>
                  <p class="col s6 right"><strong>Ubicacion:</strong> <span>{{$reserva->visitas->first()->ubicacion->nombre ?? "Aun no registra"}}</span></p>
                </div>
              </div>
              @php 
              $totalConsumo = 0; 
              $totalServicio = 0; 
              $consumo = $reserva->venta->consumo;
              @endphp
              {{-- @foreach($reserva->venta->consumos as $consumo) --}}
              <br>

              <div class="row">
                <div class="col s12">
                  <div class="col s12">
                    <h6>
                      <strong>Consumo</strong>
                    </h6>
                    @if (isset($consumo->detallesConsumos))
                      @foreach ($consumo->detallesConsumos as $detalle)
                          @php 
                            $subtotal = $detalle['subtotal'];
                            $totalConsumo += $subtotal;
                          @endphp
                          <div class="row">
                            <div class="col s8 offset-s2">
                              
                              <div class="col s6 left">
                                {{ $detalle['cantidad_producto'] }} X {{ number_format($detalle->producto->valor, 0, ',', '.') }}
                              </div>
                            </div>
                  
                          </div>
                  
                          <div class="row">
                            <div class="col s8 offset-s2">
                              <div class="col s6 left">{{ $detalle->producto->nombre }}</div>
                              <div class="col s6 right">${{ number_format($subtotal, 0, ',', '.') }}</div>
                            </div>
                          </div>
              
                          <br>
                      @endforeach
                    @endif
                  </div>

                    <br>

                  <div class="col s12">


                        <h6>
                          <strong>Servicios</strong>
                        </h6>
                        @if (isset($consumo->detalleServiciosExtra))
                          @foreach ($consumo->detalleServiciosExtra as $detalle)
                              @php 
                                $subtotal = $detalle['subtotal'];
                                $totalServicio += $subtotal;
                              @endphp
                              <div class="row">
                                <div class="col s8 offset-s2">
                                  
                                  <div class="col s6 left">
                                    {{ $detalle['cantidad_servicio'] }} X {{ number_format($detalle->servicio->valor_servicio, 0, ',', '.') }}
                                  </div>
                                </div>
                      
                              </div>
                      
                              <div class="row">
                                <div class="col s8 offset-s2">
                                  <div class="col s6 left">{{ $detalle->servicio->nombre_servicio }}</div>
                                  <div class="col s6 right">${{ number_format($subtotal, 0, ',', '.') }}</div>
                                </div>
                              </div>
                  
                              <br>
                          @endforeach
                        @endif
                  </div>
                </div>
              </div>

              {{-- @endforeach --}}
              <br><br>
              <div class="row">
                
              </div>
              <p><strong>Subtotal:</strong> ${{ number_format($totalConsumo+$totalServicio, 0, ',', '.') }}</p>
              <p><strong>Propina 10%:</strong> ${{ number_format($totalConsumo*0.1, 0, ',', '.') }}</p>
              <p><strong>Total a pagar:</strong> ${{ number_format(($totalConsumo*1.1)+$totalServicio, 0, ',', '.') }}</p>
            </div>
          </div>
          <br><br>
          <div class="modal-footer">
            <button class="waves-effect waves-light btn" onclick="enviarFormulario({{$reserva->id}});">Imprimir</button>

            <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
          </div>


          <form id="boleta-form-{{$reserva->id}}" action="{{ route('backoffice.boleta.reserva',$reserva) }}" method="POST" style="display: none;">
            @csrf
          </form>



          <script>
            function enviarFormulario(reservaId) {
                event.preventDefault();
                let form = document.getElementById('boleta-form-'+reservaId);
                form.target = "_blank";
                form.submit();
            }
            </script>













    {{-- <div class="modal-content">
      <h4 class="center">Boleta Electrónica</h4>
      <div id="boleta-contenido">
        <h5 class="center">Centro Recreativo Botacura LTDA.</h5>
        <p class="center"><strong>Fecha:</strong> <span>{{ now()->format('d-m-Y') }}</span></p>
        <p class="center"><strong>Cliente:</strong> <span>{{ $reserva->cliente->nombre_cliente }}</span></p>
        <table class="striped">
          <thead> --}}
            {{-- <tr>
              <th>Descripción</th>
              <th>Cantidad</th>
              <th>Precio</th>
              <th>Total</th>
            </tr> --}}
          {{-- </thead>
          <tbody>
            @php $total = 0; @endphp
            @foreach($reserva->venta->consumos as $consumo)
              @foreach ($consumo->detallesConsumos as $detalle)
                @php 
                  $subtotal = $detalle['subtotal'];
                  $total += $subtotal;
                @endphp
                <tr>
                  <td>{{ $detalle['cantidad_producto'] }} X {{ number_format($detalle->producto->valor, 0, ',', '.') }}</td> --}}
                  {{-- <td>${{ number_format($detalle->producto->valor, 0, ',', '.') }}</td> --}}
                  {{-- <td>${{ number_format($subtotal, 0, ',', '.') }}</td> --}}
                {{-- </tr>
                <tr>
                  <td>{{ $detalle->producto->nombre }}</td>
                  <td rowspan="2"><td>${{ number_format($subtotal, 0, ',', '.') }}</td></td>
                </tr>
              @endforeach
            @endforeach
          </tbody>
        </table>
        <br><br>
        <p class=""><strong class="">Total a pagar:</strong> ${{ number_format($total, 0, ',', '.') }}</p>
      </div>
    </div> --}}
    {{-- <div class="modal-footer">
      <button class="waves-effect waves-light btn" onclick="imprimirBoleta()">Imprimir</button>
      <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
    </div> --}}
  </div>
  
  {{-- <script>
    document.addEventListener('DOMContentLoaded', function() {
      M.Modal.init(document.querySelectorAll('.modal'));
    });
  
    function imprimirBoleta() {
      let contenido = document.getElementById("boleta-contenido").innerHTML;
      let ventana = window.open('', '', 'width=300,height=600');
      ventana.document.write(`
        <html>
        <head>
          <title>Boleta</title>
          <style>
            body { font-family: Arial, sans-serif; font-size: 12px; text-align: center; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border-bottom: 1px dashed black; padding: 5px; text-align: left; }
            th { text-align: center; }
          </style>
        </head>
        <body>
          ${contenido}
          <script>
            window.onload = function() { window.print(); window.close(); };
          </script>
        </body>
        </html>
      `);
      ventana.document.close();
    }
  </script> --}}