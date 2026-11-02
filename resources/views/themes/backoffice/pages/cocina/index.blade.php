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
{{-- Ingresar postres del día (cocina) --}}
<div id="modalPostres" class="modal" style="max-width:480px">
  <form onsubmit="return postreAgregar(event)" style="margin:0">
    <div class="modal-content">
      <h5 style="margin-top:0">Ingresar postres</h5>
      <p class="grey-text" style="margin-top:0">Día <span id="postre-fecha"></span>. Si el postre ya está, se suma la cantidad.</p>
      <div class="input-field">
        <input id="postre-nombre" type="text" list="postres-sugeridos" maxlength="120" required autocomplete="off">
        <label for="postre-nombre">Nombre del postre</label>
      </div>
      <div class="input-field">
        <input id="postre-cantidad" type="number" min="1" max="999" required>
        <label for="postre-cantidad">Cantidad</label>
      </div>
      <datalist id="postres-sugeridos"></datalist>
    </div>
    <div class="modal-footer">
      <a href="#!" class="modal-close btn-flat">Cancelar</a>
      <button type="submit" class="btn pink">Agregar</button>
    </div>
  </form>
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
    var tieneMenus = reserva.menus && reserva.menus.length;
    var tieneBuffet = reserva.desayuno_once && reserva.desayuno_once.length;
    if (!tieneMenus && !tieneBuffet) return '';

    var btnDisplay = (reserva.avisado_en_cocina === null || reserva.avisado_en_cocina === 'avisado' || reserva.avisado_en_cocina === 'entregado')
      ? 'display:none;'
      : '';

    var rows = tieneMenus ? reserva.menus.map(function(menu, idx) {
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
    }).join('') : '';

    var tablaMenus = tieneMenus ? `
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
    ` : `<p>No registra menú de almuerzo.</p>`;

    var buffetTexto = '';
    if (tieneBuffet) {
      var etiquetas = reserva.desayuno_once.map(function(t) { return t === 'desayuno' ? 'Desayuno' : 'Once'; });
      buffetTexto = etiquetas.join(' y ');
    }
    var buffetInfo = buffetTexto ? `<p><i class="material-icons tiny">free_breakfast</i> ${escapeHtml(buffetTexto)} (${escapeHtml(reserva.cantidad_personas)})</p>` : '';

    return `
      <div class="card-panel">
        <div class="card-content gradient-45deg-light-blue-cyan">
          <h5 class="card-title center white-text">
            <i class="material-icons white-text">restaurant_menu</i>
            Menús para ${escapeHtml(reserva.cliente)} (${escapeHtml(reserva.cantidad_personas)} ${reserva.cantidad_personas >= 1 ? (reserva.cantidad_personas > 2 ? "Comensales" : "Comensal") : ""}) - ${escapeHtml(reserva.programa)}

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
          ${buffetInfo}
          ${tablaMenus}
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


  // ===== Postres del día =====
  var POSTRES_STORE = "{{ route('backoffice.cocina.postres.store') }}";
  var POSTRES_ITEM  = "{{ route('backoffice.cocina.postres.update', '__ID__') }}";

  function renderPostres(data) {
    var lista = data.postres || [];
    var puede = !!data.puede_postres;
    var total = lista.reduce(function(s, p) { return s + (p.cantidad || 0); }, 0);
    var dl = document.getElementById('postres-sugeridos');
    if (dl) dl.innerHTML = (data.postres_sugeridos || []).map(function(n) { return '<option value="' + escapeHtml(n) + '">'; }).join('');

    var btnRedondo = 'width:32px;height:32px;border-radius:50%;border:1px solid #bdbdbd;background:#fff;color:#424242;font-size:18px;line-height:28px;padding:0;cursor:pointer';
    var items = lista.length ? lista.map(function(p) {
      var acciones = puede
        ? `<button type="button" style="${btnRedondo}" onclick="postreCantidad(${p.id}, ${p.cantidad - 1})" aria-label="Restar uno a ${escapeHtml(p.nombre)}" ${p.cantidad <= 0 ? 'disabled' : ''}>−</button>
           <strong style="min-width:34px;text-align:center;font-size:16px">${p.cantidad}</strong>
           <button type="button" style="${btnRedondo}" onclick="postreCantidad(${p.id}, ${p.cantidad + 1})" aria-label="Sumar uno a ${escapeHtml(p.nombre)}">+</button>
           <button type="button" style="border:0;background:none;cursor:pointer;margin-left:6px;padding:4px" onclick="postreQuitar(${p.id})" aria-label="Quitar ${escapeHtml(p.nombre)}" title="Quitar"><i class="material-icons grey-text" style="font-size:20px">delete_outline</i></button>`
        : `<strong style="font-size:16px">${p.cantidad}</strong> <span class="grey-text">${p.cantidad === 1 ? 'unidad' : 'unidades'}</span>`;
      return `
        <li class="collection-item">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap">
            <p class="collections-title" style="margin:0">${escapeHtml(p.nombre)}</p>
            <div style="display:flex;align-items:center;gap:6px">${acciones}</div>
          </div>
        </li>`;
    }).join('') : '<li class="collection-item grey-text">Aún no se ingresan postres para este día.</li>';

    var boton = puede
      ? `<a href="javascript:void(0)" onclick="abrirPostres()" class="btn pink waves-effect waves-light" style="position:absolute;right:16px;top:50%;transform:translateY(-50%)"><i class="material-icons left">add</i>Ingresar postres</a>`
      : '';

    return `
      <ul class="collection z-depth-1">
        <li class="collection-item avatar" style="position:relative;min-height:72px">
          <i class="material-icons pink circle">cake</i>
          <h6 class="collection-header m-0">Postres del día</h6>
          <p>Total: ${total}</p>
          ${boton}
        </li>
        ${items}
      </ul>`;
  }

  function abrirPostres() {
    var el = document.getElementById('modalPostres');
    if (!el) return;
    document.getElementById('postre-nombre').value = '';
    document.getElementById('postre-cantidad').value = '';
    var f = document.getElementById('postre-fecha');
    if (f) f.textContent = window.__fechaPostres || '';
    $(el).modal('open');
    setTimeout(function() { document.getElementById('postre-nombre').focus(); }, 250);
  }

  function postresPeticion(url, metodo, cuerpo) {
    cuerpo = cuerpo || {};
    if (metodo !== 'POST') cuerpo._method = metodo;
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(cuerpo)
    })
    .then(function(r) {
      return r.json().catch(function() { return {}; }).then(function(j) {
        if (!r.ok) {
          var msg = j.errors ? Object.values(j.errors)[0][0] : (j.message || ('Error ' + r.status));
          throw new Error(msg);
        }
        return j;
      });
    })
    .then(function(j) {
      var col = document.getElementById('postres-col');
      if (col) col.innerHTML = renderPostres({ postres: j.postres, postres_sugeridos: j.sugeridos, puede_postres: j.puede });
      return true;
    })
    .catch(function(e) {
      if (typeof Swal !== 'undefined') { Swal.fire('No se pudo guardar', e.message, 'error'); } else { alert(e.message); }
      return false;
    });
  }

  function postreAgregar(ev) {
    ev.preventDefault();
    var nombre = document.getElementById('postre-nombre').value.trim();
    var cantidad = parseInt(document.getElementById('postre-cantidad').value, 10);
    if (!nombre || !(cantidad > 0)) return false;
    postresPeticion(POSTRES_STORE, 'POST', { fecha: window.__fechaPostres, nombre: nombre, cantidad: cantidad })
      .then(function(ok) { if (ok) $('#modalPostres').modal('close'); });
    return false;
  }

  function postreCantidad(id, cantidad) {
    if (cantidad < 0) return;
    postresPeticion(POSTRES_ITEM.replace('__ID__', id), 'PUT', { cantidad: cantidad });
  }

  function postreQuitar(id) {
    if (!confirm('¿Quitar este postre del día?')) return;
    postresPeticion(POSTRES_ITEM.replace('__ID__', id), 'DELETE', {});
  }

  $(function() { $('#modalPostres').modal(); });

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
        window.__fechaPostres = data.fecha;

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
            <div class="row">
              <div class="col s12 m4 l4">
                ${renderContador('Desayuno / Once', 'free_breakfast', 'purple', data.buffet)}
              </div>
              <div class="col s12 m8 l8" id="postres-col">
                ${renderPostres(data)}
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
