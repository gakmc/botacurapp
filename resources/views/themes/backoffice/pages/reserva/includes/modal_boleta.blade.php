<!-- Modal Structure -->
<div id="modal-{{$reserva->venta->consumos->first()->id}}" class="modal modal-fixed-footer">
    <div class="modal-content">
      <h4>Boleta</h4>
      <div id="boleta-contenido">
        <h5>Mi Comercio</h5>
        <p><strong>Fecha:</strong> <span>{{ now()->format('d-m-Y') }}</span></p>
        <p><strong>Cliente:</strong> <span>{{-- $cliente --}}</span></p>
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
            {{-- @php $total = 0; @endphp
            @foreach($detalles as $item)
              @php 
                $subtotal = $item['cantidad'] * $item['precio'];
                $total += $subtotal;
              @endphp
              <tr>
                <td>{{ $item['descripcion'] }}</td>
                <td>{{ $item['cantidad'] }}</td>
                <td>${{ number_format($item['precio'], 0, ',', '.') }}</td>
                <td>${{ number_format($subtotal, 0, ',', '.') }}</td>
              </tr>
            @endforeach --}}
          </tbody>
        </table>
        <p><strong>Total a pagar:</strong> ${{-- number_format($total, 0, ',', '.') --}}</p>
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