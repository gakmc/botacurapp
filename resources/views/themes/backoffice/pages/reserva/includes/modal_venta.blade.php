<div id="modalVenta" class="modal">
  <div class="modal-content">
    <h4>Detalles de la Venta</h4>
    <div class="col s12 m6">

      <p><strong>Abono:</strong><span id="modalAbono"></span></p>

      <p><strong>Tipo de Transaccion (Abono):</strong> <span id="modalTipoAbono"></span></p>

      <p><strong>Descuento:</strong> <span id="modalDescuento"> </span></p>
    </div>

    <div class="col s12 m6">

      <p><strong>Diferencia Pagada:</strong><span id="modalDiferencia"> </span></p>

      <p><strong>Tipo de Transaccion (Diferencia):</strong> <span id="modalTipoDiferencia"></span></p>

      <p><strong>Diferencia por pagar:</strong><span id="modalTotalPagar"></span></p>
    </div>


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



    <div class="col s12" style="display:flex; justify-content:space-around; align-items:center;">
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
    </div>


  </div>
  <div class="modal-footer">
    <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cerrar</a>
  </div>
</div>