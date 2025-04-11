<div id="modalVenta" class="modal">
  <div class="modal-content">
    <h4>Detalles de la Venta</h4>


    <div class="col s12">
      <p><strong>Consumo:</strong><span id="modalConsumo"></span></p>

    </div>

    <div class="col s12">
      <p><strong>Servicio:</strong><span id="modalServicio"></span></p>
    </div>

    <div class="col s12">
      <p><strong>Resumen:</strong><span id="modalResumen"></span></p>
    </div>


    <div class="col s12">
      <br><br>
    </div>



    {{-- <div class="col s12" style="display:flex; justify-content:space-around; align-items:center;">
      <p><strong>Imagen Abono:</strong></p>
      <a href="" target="_blank" id="linkAbono">
        <img style="max-width:300px; max-height:200px;" id="modalAbonoImg" />
      </a>

      <p><strong>Imagen Diferencia:</strong></p>
      <a href="" target="_blank" id="linkDiferencia">
        <img style="max-width:300px; max-height:200px;" id="modalDiferenciaImg" />
      </a>
    </div>


    <div class="col s12" style="display:flex; justify-content:start; align-items:center;" id="consumoSeparado" hidden>
      <p id="pConsumoSeparado"><strong>Imagen Pago Consumo:</strong></p>
      <a href="" target="_blank" id="linkConsumo">
        <img style="max-width:300px; max-height:200px;" id="modalConsumoImg" />
      </a>
    </div> --}}


  </div>
  <div class="modal-footer">
    <button class="waves-effect waves-light btn" onclick="enviarFormulario({{$reserva->id}});">Imprimir</button>

    <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cerrar</a>
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


</div>