<!-- Modal Structure -->
<div id="modal-{{$reserva->id}}" class="modal modal-fixed-footer">
    <div class="modal-content">
      <h4 class="center">Boleta Electrónica</h4>
      <div id="boleta-contenido">
        <h5 class="center">Centro Recreativo Botacura LTDA.</h5>
        <p class="center"><strong>Fecha:</strong> <span>{{ now()->format('d-m-Y') }}</span></p>
        <p class="center"><strong>Cliente:</strong> <span>{{ $reserva->cliente->nombre_cliente }}</span></p>
        <table>
          <thead>
            <tr>
              <th>Descripción</th>
              <th>Cantidad</th>
              <th>Precio</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            @php $total = 0; @endphp
            @foreach($reserva->venta->consumos as $consumo)
              @foreach ($consumo->detallesConsumos as $detalle)
                @php 
                  $subtotal = $detalle['subtotal'];
                  $total += $subtotal;
                @endphp
                <tr>
                  <td>{{ $detalle->producto->nombre }}</td>
                  <td>x{{ $detalle['cantidad_producto'] }}</td>
                  <td>${{ number_format($detalle->producto->valor, 0, ',', '.') }}</td>
                  <td>${{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
              @endforeach
            @endforeach
          </tbody>
        </table>
        <br><br>
        <p class=""><strong class="">Total a pagar:</strong> ${{ number_format($total, 0, ',', '.') }}</p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="waves-effect waves-light btn" onclick="imprimirBoleta()">Imprimir</button>
      <a href="#!" class="modal-action modal-close waves-effect waves-red btn-flat">Cerrar</a>
    </div>
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