{{-- Programas adicionales en la misma reserva (15/10/2026). Ej.: 2 Wellness Day + 1 Full Day. --}}
@php
  $programasExtra = $programasExtra ?? collect();
  $listaProgramas = $programas->sortBy('nombre_programa');
@endphp
<div class="row" id="programas-extra-box" style="margin-bottom:0">
  <div class="col s12">
    <p style="margin:0 0 6px 0">
      <strong>¿Otro programa en la misma reserva?</strong>
      <span class="grey-text">(ej.: 2 personas en Wellness Day y 1 en Full Day)</span>
    </p>
    <div id="programas-extra-filas">
      @foreach ($programasExtra as $i => $px)
        <div class="row programa-extra-fila" style="margin-bottom:4px">
          <div class="col s7 m6">
            <select class="browser-default programa-extra-select" name="programas_extra[{{ $i }}][id_programa]">
              <option value="">-- Programa --</option>
              @foreach ($listaProgramas as $pr)
                <option value="{{ $pr->id }}" data-valor="{{ ((int) $pr->id === (int) $px->id_programa && $px->valor_unitario) ? $px->valor_unitario : $pr->valor_programa }}"
                  @if ((int) $pr->id === (int) $px->id_programa) selected @endif>{{ $pr->nombre_programa }}</option>
              @endforeach
            </select>
          </div>
          <div class="col s3 m3">
            <input type="number" min="1" class="programa-extra-personas" name="programas_extra[{{ $i }}][personas]" value="{{ $px->personas }}" placeholder="Personas">
          </div>
          <div class="col s2 m3">
            <a href="#!" class="btn-flat red-text programa-extra-quitar" title="Quitar"><i class="material-icons">close</i></a>
          </div>
        </div>
      @endforeach
    </div>
    <a href="#!" class="btn-small waves-effect" id="programa-extra-agregar"><i class="material-icons left">add</i>Agregar programa</a>
    <span id="programas-total-personas" class="grey-text" style="margin-left:12px"></span>
  </div>
</div>
<template id="programa-extra-plantilla">
  <div class="row programa-extra-fila" style="margin-bottom:4px">
    <div class="col s7 m6">
      <select class="browser-default programa-extra-select" name="programas_extra[__i__][id_programa]">
        <option value="">-- Programa --</option>
        @foreach ($listaProgramas as $pr)
          <option value="{{ $pr->id }}" data-valor="{{ $pr->valor_programa }}">{{ $pr->nombre_programa }}</option>
        @endforeach
      </select>
    </div>
    <div class="col s3 m3">
      <input type="number" min="1" class="programa-extra-personas" name="programas_extra[__i__][personas]" placeholder="Personas">
    </div>
    <div class="col s2 m3">
      <a href="#!" class="btn-flat red-text programa-extra-quitar" title="Quitar"><i class="material-icons">close</i></a>
    </div>
  </div>
</template>
<script>
  (function () {
    var cont = document.getElementById('programas-extra-filas');
    var plantilla = document.getElementById('programa-extra-plantilla');
    var indice = cont.querySelectorAll('.programa-extra-fila').length + 100;

    // Monto de los programas adicionales (valor por persona x personas)
    window.montoProgramasExtra = function () {
      var total = 0;
      cont.querySelectorAll('.programa-extra-fila').forEach(function (f) {
        var sel = f.querySelector('select');
        var opt = sel.options[sel.selectedIndex];
        var valor = opt ? parseInt(opt.getAttribute('data-valor') || '0', 10) : 0;
        var personas = parseInt(f.querySelector('.programa-extra-personas').value || '0', 10);
        if (sel.value && personas > 0) total += Math.round(valor * personas);
      });
      return total;
    };
    window.personasProgramasExtra = function () {
      var n = 0;
      cont.querySelectorAll('.programa-extra-fila').forEach(function (f) {
        var p = parseInt(f.querySelector('.programa-extra-personas').value || '0', 10);
        if (f.querySelector('select').value && p > 0) n += p;
      });
      return n;
    };

    function refrescar(e) {
      var principal = parseInt((document.getElementById('cantidad_personas') || {}).value || '0', 10) || 0;
      var extra = window.personasProgramasExtra();
      var etiqueta = document.getElementById('programas-total-personas');
      etiqueta.textContent = extra > 0 ? ('Total de la reserva: ' + (principal + extra) + ' personas') : '';
      var lbl = document.querySelector('label[for="cantidad_personas"]');
      if (lbl) lbl.textContent = extra > 0 ? 'Personas en este programa' : 'Cantidad Personas';
      if (e !== 'inicio' && typeof calcularValorTotal === 'function') calcularValorTotal();
    }

    document.getElementById('programa-extra-agregar').addEventListener('click', function (e) {
      e.preventDefault();
      var html = plantilla.innerHTML.replace(/__i__/g, String(indice++));
      var tmp = document.createElement('div');
      tmp.innerHTML = html.trim();
      cont.appendChild(tmp.firstChild);
      refrescar();
    });
    cont.addEventListener('click', function (e) {
      var b = e.target.closest('.programa-extra-quitar');
      if (!b) return;
      e.preventDefault();
      b.closest('.programa-extra-fila').remove();
      refrescar();
    });
    cont.addEventListener('change', refrescar);
    cont.addEventListener('input', refrescar);
    var cp = document.getElementById('cantidad_personas');
    if (cp) { cp.addEventListener('change', refrescar); cp.addEventListener('input', refrescar); }
    document.addEventListener('DOMContentLoaded', function () { refrescar('inicio'); });
  })();
</script>
