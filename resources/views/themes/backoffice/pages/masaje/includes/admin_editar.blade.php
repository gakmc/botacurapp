{{-- Editar / borrar masajes del listado: solo administrador (15/10/2026) --}}
<div id="modal-masaje-admin" class="modal">
  <form id="form-masaje-admin" method="POST" action="">
    @csrf
    @method('PUT')
    <div class="modal-content">
      <h5>Editar masaje <small id="masaje-admin-cliente" class="grey-text"></small></h5>
      <div class="row">
        <div class="col s6 m4">
          <label>Hora de inicio</label>
          <input type="time" step="600" name="horario_masaje" id="masaje-admin-horario">
          <small class="grey-text">Vacío = sin horario</small>
        </div>
        <div class="col s6 m4">
          <label>Persona N°</label>
          <input type="number" min="1" name="persona" id="masaje-admin-persona" required>
        </div>
        <div class="col s12 m4" style="padding-top:22px">
          <label>
            <input type="checkbox" name="tiempo_extra" value="1" id="masaje-admin-extra">
            <span>Tiempo extra (60 min)</span>
          </label>
        </div>
      </div>
      <div class="row">
        <div class="col s12 m4">
          <label>Tipo de masaje</label>
          <select class="browser-default" name="tipo_masaje" id="masaje-admin-tipo">
            <option value="">-- Sin tipo --</option>
            @foreach (($tiposMasajeLista ?? collect()) as $t)
              <option value="{{ $t }}">{{ $t }}</option>
            @endforeach
          </select>
        </div>
        <div class="col s12 m4">
          <label>Lugar</label>
          <select class="browser-default" name="id_lugar_masaje" id="masaje-admin-lugar" required>
            @foreach ($lugares as $l)
              <option value="{{ $l->id }}">{{ $l->nombre }}</option>
            @endforeach
          </select>
        </div>
        <div class="col s12 m4">
          <label>Masoterapeuta</label>
          <select class="browser-default" name="user_id" id="masaje-admin-user">
            <option value="">-- No asignado --</option>
            @foreach (($masoterapeutasLista ?? collect()) as $u)
              <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <a href="#!" class="modal-close modal-action btn-flat">Cancelar</a>
      <button type="submit" class="btn waves-effect">Guardar</button>
    </div>
  </form>
</div>

<div id="modal-masaje-borrar" class="modal">
  <form id="form-masaje-borrar" method="POST" action="">
    @csrf
    @method('DELETE')
    <div class="modal-content">
      <h5>¿Borrar este masaje?</h5>
      <p id="masaje-borrar-texto"></p>
      <p class="grey-text">Se quita del listado. Si era un masaje extra ya cobrado, el cobro no cambia.</p>
    </div>
    <div class="modal-footer">
      <a href="#!" class="modal-close modal-action btn-flat">Cancelar</a>
      <button type="submit" class="btn red waves-effect">Borrar</button>
    </div>
  </form>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    var abrir = function (id) {
      if ($ && $.fn.modal) { $(id).modal(); $(id).modal('open'); }
      else { document.querySelector(id).style.display = 'block'; }
    };
    var setSel = function (el, v) {
      el.value = v || '';
      if (v && el.value !== String(v)) { // valor que no esta en la lista: se agrega
        var o = document.createElement('option'); o.value = v; o.textContent = v; el.appendChild(o); el.value = v;
      }
    };
    document.addEventListener('click', function (e) {
      var ed = e.target.closest('.masaje-editar');
      if (ed) {
        e.preventDefault();
        document.getElementById('form-masaje-admin').action = ed.dataset.url;
        document.getElementById('masaje-admin-cliente').textContent = ed.dataset.cliente || '';
        document.getElementById('masaje-admin-horario').value = ed.dataset.horario || '';
        document.getElementById('masaje-admin-persona').value = ed.dataset.persona || 1;
        document.getElementById('masaje-admin-extra').checked = ed.dataset.extra === '1';
        setSel(document.getElementById('masaje-admin-tipo'), ed.dataset.tipo);
        document.getElementById('masaje-admin-lugar').value = ed.dataset.lugar || '';
        document.getElementById('masaje-admin-user').value = ed.dataset.user || '';
        abrir('#modal-masaje-admin');
        return;
      }
      var bo = e.target.closest('.masaje-borrar');
      if (bo) {
        e.preventDefault();
        document.getElementById('form-masaje-borrar').action = bo.dataset.url;
        document.getElementById('masaje-borrar-texto').textContent = bo.dataset.texto || '';
        abrir('#modal-masaje-borrar');
      }
    });
  });
</script>
