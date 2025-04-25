
<div id="modal{{$ventaDirecta->id}}" class="modal modal-fixed-footer">

    <div class="modal-content">
        <div class="boleta-contenido">
            <div class="row">
                <div class="col s12 center">
                    <img src="{{asset('images/logo/logo.png')}}" alt="logo">
                    <h5 class="col s12 center">Centro Recreativo Botacura LTDA.</h5>
                  <h6 class="col s12 center"><strong>Rut: </strong>77.848.621-0</h6>
                  <h6 class="col s12 center"><strong>Dirección: </strong>Cam. Al Volcán 13274, El Manzano, San José de Maipo, Región Metropolitana</h6>
                </div>
            </div>

            <div class="row">
                <div class="col s12">
                    <p class="col s6 left"><strong>Generada por:</strong> <span>{{ $ventaDirecta->user->name }}</span></p>
                    <p class="col s6 right"><strong>Fecha:</strong> <span>{{ $ventaDirecta->fecha->format('d-m-Y') }}</span></p>
                </div>
            </div>

            <div class="row">
                <div class="col s12">
                  <p class="col s6 left"><strong>Cliente:</strong> <span> No Aplica </span></p>
                  <p class="col s6 right"><strong>Ubicacion:</strong> <span> No Aplica</span></p>
                </div>
              </div>
              @php 
                $total = 0; 
              @endphp

<br>
<div class="row">
    <div class="col s12">
      <div class="col s12">
        <h6>
          <strong>Productos:</strong>
        </h6>
        @if (isset($ventaDirecta->detalles))
          @foreach ($ventaDirecta->detalles as $detalle)
              @php 
                $subtotal = $detalle['subtotal'];
                $total += $subtotal;
              @endphp
              <div class="row">
                <div class="col s8 offset-s2">
                  
                  <div class="col s6 left">
                    {{ $detalle['cantidad'] }} X {{ number_format($detalle->producto->valor, 0, ',', '.') }}
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


    </div>
</div>

<br><br>

<div class="row">
</div>
<p><strong>Subtotal:</strong> ${{ number_format($total, 0, ',', '.') }}</p>
<p><strong>Propina 10%:</strong> ${{ number_format($ventaDirecta->valor_propina, 0, ',', '.') }}</p>
<p><strong>Total a pagar:</strong> ${{ number_format($ventaDirecta->total, 0, ',', '.') }}</p>

        </div>
      {{-- <h4>Modal de la venta #{{$ventaDirecta->detalles}}</h4>
      <p>A bunch of text</p> --}}
    </div>





    <div class="modal-footer">
      <button class="waves-effect waves-light btn" onclick="enviarFormulario({{$ventaDirecta->id}});">Imprimir</button>

      <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
    </div>


    <form id="boleta-form-{{$ventaDirecta->id}}" action="{{ route('backoffice.boleta.venta_directa',$ventaDirecta) }}" method="POST" style="display: none;">
      @csrf
    </form>



    <script>
      function enviarFormulario(ventaDirectaId) {
          event.preventDefault();
          let form = document.getElementById('boleta-form-'+ventaDirectaId);
          form.target = "_blank";
          form.submit();
      }
      </script>



</div>