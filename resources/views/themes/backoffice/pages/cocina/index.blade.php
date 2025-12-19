@extends('themes.backoffice.layouts.admin')

@section('title', 'Menús')

@section('content')
<div class="section">
  <p class="caption">
    <strong>
      Menús desde
      <a href="javascript:void(0)" id="fecha-label">{{ $fechaInicial }}</a>
    </strong>
  </p>

  <div class="divider"></div>

  <div class="section">
    <div class="card-panel">

      <div class="row" style="margin-bottom: 0;">
        <div class="col s12 m6">
          <a class="btn" id="btn-prev">
            <i class="material-icons left">chevron_left</i> Día anterior
          </a>

          <a class="btn" id="btn-next">
            Día siguiente <i class="material-icons right">chevron_right</i>
          </a>
        </div>

        <div class="col s12 m6 right-align">
          <div class="input-field" style="margin:0;">
            <input type="text" id="fecha-input" value="{{ $fechaInicial }}" placeholder="dd-mm-aaaa">
            <label for="fecha-input" class="active">Ir a fecha</label>
          </div>
          <a class="btn teal" id="btn-ir">
            <i class="material-icons left">search</i> Cargar
          </a>
        </div>
      </div>

      <div id="menus-content" style="margin-top: 15px;">
        {{-- Aquí renderiza JS --}}
      </div>

    </div>
  </div>
</div>
@endsection

@section('foot')
<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
  // ===== Helpers Fecha =====
  function parseDMY(str) {
    // str: dd-mm-YYYY
    var p = str.split('-');
    if (p.length !== 3) return null;
    var d = parseInt(p[0], 10);
    var m = parseInt(p[1], 10) - 1;
    var y = parseInt(p[2], 10);
    var dt = new Date(y, m, d);
    if (dt.getFullYear() !== y || dt.getMonth() !== m || dt.getDate() !== d) return null;
    return dt;
  }

  function formatDMY(date) {
    var d = String(date.getDate()).padStart(2, '0');
    var m = String(date.getMonth()+1).padStart(2, '0');
    var y = date.getFullYear();
    return d + '-' + m + '-' + y;
  }

  function addDays(d, days) {
    var x = new Date(d.getTime());
    x.setDate(x.getDate() + days);
    return x;
  }

  // ===== Renderizar HTML =====
  function renderContador(titulo, icon, colorClass, dataObj) {
    var keys = Object.keys(dataObj || {});
    if (!keys.length) {
      return '<p>No hay platos para esta fecha.</p>';
    }

    var items = keys.map(function(nombre) {
      var cantidad = dataObj[nombre];
      var texto = (cantidad <= 1) ? 'Plato' : 'Platos';
      return `
        <li class="collection-item">
          <div class="row" style="margin-bottom:0;">
            <div class="col s9">
              <p class="collections-title">${escapeHtml(nombre)}:</p>
            </div>
            <div class="col s3">
              <span class="task-cat ${colorClass}">
                <strong>${cantidad}</strong> ${texto}
              </span>
            </div>
          </div>
        </li>`;
    }).join('');

    return `
      <ul class="collection z-depth-1">
        <li class="collection-item avatar">
          <i class="material-icons ${colorClass} circle">${icon}</i>
          <h6 class="collection-header m-0">${titulo}</h6>
          <p>Total</p>
        </li>
        ${items}
      </ul>`;
  }

  function renderReservaCard(reserva) {
    if (!reserva.menus || !reserva.menus.length) return '';

    var btnDisplay = (reserva.avisado_en_cocina === null || reserva.avisado_en_cocina === 'avisado' || reserva.avisado_en_cocina === 'entregado')
      ? 'display:none;'
      : '';

    var rows = reserva.menus.map(function(menu, idx) {
      var entrada = menu.entrada ? escapeHtml(menu.entrada) : '<span class="red-text">No registra</span>';
      var fondo = menu.fondo ? escapeHtml(menu.fondo) : '<span class="red-text">No registra</span>';
      var acomp = menu.acompanamiento ? escapeHtml(menu.acompanamiento) : 'Sin Acompañamiento';

      var alergias = menu.alergias ? `<td style="color:red">${escapeHtml(menu.alergias)}</td>` : `<td>No Registra</td>`;
      var obs = menu.observacion ? `<td style="color:red">${escapeHtml(menu.observacion)}</td>` : `<td>No Registra</td>`;

      return `
        <tr>
          <td><strong>Menú ${idx + 1}:</strong></td>
          <td>${entrada}</td>
          <td>${fondo}</td>
          <td>${acomp}</td>
          ${alergias}
          ${obs}
        </tr>
      `;
    }).join('');

    return `
      <div class="card-panel">
        <div class="card-content gradient-45deg-light-blue-cyan">
          <h5 class="card-title center white-text">
            <i class="material-icons white-text">restaurant_menu</i>
            Menús para ${escapeHtml(reserva.cliente)} - ${escapeHtml(reserva.programa)}

            <button
              id="avisar_${reserva.id}"
              class="btn-floating btn-avisar"
              style="${btnDisplay}"
              data-url="${reserva.avisar_url}"
              onclick="darAviso(${reserva.id})"
            >
              <i class="material-icons">notifications_active</i>
            </button>
          </h5>
        </div>

        <div class="card-content grey lighten-4">
          <table class="responsive-table">
            <thead>
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
              ${rows}
            </tbody>
          </table>
        </div>
      </div>
    `;
  }

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  // ===== AJAX =====
  function cargarDia(fechaDMY) {
    var cont = document.getElementById('menus-content');
    cont.innerHTML = `
      <div class="center" style="padding:20px;">
        <div class="preloader-wrapper active">
          <div class="spinner-layer spinner-blue-only">
            <div class="circle-clipper left"><div class="circle"></div></div>
            <div class="gap-patch"><div class="circle"></div></div>
            <div class="circle-clipper right"><div class="circle"></div></div>
          </div>
        </div>
        <p>Cargando menús...</p>
      </div>
    `;

    document.getElementById('fecha-label').textContent = fechaDMY;
    document.getElementById('fecha-input').value = fechaDMY;

    var url = "{{ route('backoffice.cocina.dia') }}" + "?fecha=" + encodeURIComponent(fechaDMY);

    fetch(url)
      .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function(data) {

        // Cabecera día
        var tituloDia = data.hoy ? 'Hoy' : data.fecha;

        // 3 columnas (entradas / fondos / acomp)
        var cols = `
          <h5>${tituloDia} ${data.hoy ? data.fecha : ''}</h5>
          <div id="work-collections">
            <div class="row">
              <div class="col s12 m4 l4">
                ${renderContador('Platos de Entrada', 'restaurant_menu', 'teal', data.entradas)}
              </div>
              <div class="col s12 m4 l4">
                ${renderContador('Platos de Fondo', 'room_service', 'cyan', data.fondos)}
              </div>
              <div class="col s12 m4 l4">
                ${renderContador('Acompañamientos', 'restaurant', 'orange', data.acompanamientos)}
              </div>
            </div>
          </div>
        `;

        // Cards reservas
        var cards = '';
        if (data.reservas && data.reservas.length) {
          data.reservas.forEach(function(res) {
            cards += renderReservaCard(res);
          });
        }

        if (!cards) {
          cards = `<p class="grey-text">No hay menús registrados para este día.</p>`;
        }

        cont.innerHTML = cols + cards;
      })
      .catch(function(err) {
        console.error(err);
        cont.innerHTML = `<p style="color:red;">Error cargando menús: ${escapeHtml(err.message)}</p>`;
      });
  }


  function darAviso(reservaId) {
    var btn = document.getElementById('avisar_' + reservaId);
    if (!btn) return;

    var url = btn.getAttribute('data-url');

    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ _method: 'PUT', id: reservaId })
    })
    .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(function(resp) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'success',
          title: 'Éxito',
          text: 'Se avisó en cocina respecto al menú de ' + (resp.nombreCliente || ''),
          confirmButtonText: 'OK'
        });
      }
      btn.style.display = 'none';
    })
    .catch(function() {
      if (typeof Swal !== 'undefined') {
        Swal.fire('Error', 'No se pudo registrar la recepción.', 'error');
      }
    });
  }

  // ===== Init =====
  // document.addEventListener('DOMContentLoaded', function() {
  //   // fecha inicial
  //   var fecha = "{{ $fechaInicial }}";
  //   cargarDia(fecha);

  //   document.getElementById('btn-prev').addEventListener('click', function() {
  //     var dt = parseDMY(document.getElementById('fecha-input').value);
  //     if (!dt) return;
  //     cargarDia(formatDMY(addDays(dt, -1)));
  //   });

  //   document.getElementById('btn-next').addEventListener('click', function() {
  //     var dt = parseDMY(document.getElementById('fecha-input').value);
  //     if (!dt) return;
  //     cargarDia(formatDMY(addDays(dt, 1)));
  //   });

  //   document.getElementById('btn-ir').addEventListener('click', function() {
  //     var dt = parseDMY(document.getElementById('fecha-input').value);
  //     if (!dt) {
  //       alert('Formato inválido. Usa dd-mm-aaaa');
  //       return;
  //     }
  //     cargarDia(formatDMY(dt));
  //   });
  // });



  document.addEventListener('DOMContentLoaded', function() {

    // EVITA doble inicialización (causa principal del salto de 2 días)
    if (window.__cocinaMenusInitialized) return;
    window.__cocinaMenusInitialized = true;

    // fecha inicial
    var fecha = "{{ $fechaInicial }}";
    cargarDia(fecha);

    // Helpers para asegurar 1 solo listener
    function bindClickOnce(el, handler) {
      if (!el) return;
      // “Resetea” listeners clonando el nodo (elimina listeners previos)
      var clone = el.cloneNode(true);
      el.parentNode.replaceChild(clone, el);
      clone.addEventListener('click', handler);
      return clone;
    }

    var prevBtn = document.getElementById('btn-prev');
    var nextBtn = document.getElementById('btn-next');
    var irBtn   = document.getElementById('btn-ir');

    prevBtn = bindClickOnce(prevBtn, function(e) {
      e.preventDefault();
      var dt = parseDMY(document.getElementById('fecha-input').value);
      if (!dt) return;
      cargarDia(formatDMY(addDays(dt, -1)));
    });

    nextBtn = bindClickOnce(nextBtn, function(e) {
      e.preventDefault();
      var dt = parseDMY(document.getElementById('fecha-input').value);
      if (!dt) return;
      cargarDia(formatDMY(addDays(dt, 1)));
    });

    irBtn = bindClickOnce(irBtn, function(e) {
      e.preventDefault();
      var dt = parseDMY(document.getElementById('fecha-input').value);
      if (!dt) {
        alert('Formato inválido. Usa dd-mm-aaaa');
        return;
      }
      cargarDia(formatDMY(dt));
    });

  });

</script>


<script>
  $(document).ready(function () {
    if (typeof window.Echo !== 'undefined') {
      window.Echo.channel('aviso-cocina')
        .listen('.reservaAvisada', (e) => {
          const boton = $(`#avisar_${e.reservaId}`);
          boton.hide();
        });
    }
  });
</script>
@endsection
