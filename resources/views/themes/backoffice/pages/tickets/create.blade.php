@extends('themes.backoffice.layouts.admin')

@section('title', 'Nuevo ticket')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{ route('backoffice.tickets.index') }}">Tickets</a></li>
<li>Nuevo</li>
@endsection

@section('dropdown_settings')
@endsection

@section('content')
<div class="section">
    <div class="row">
        <div class="col s12 m10 offset-m1 l8 offset-l2">
            <form method="POST" action="{{ route('backoffice.tickets.store') }}" enctype="multipart/form-data" id="tk-form" novalidate>
                @csrf
                <div class="card-panel">
                    <h5 class="header2">Nuevo ticket</h5>

                    <p class="red-text text-darken-2 tk-error" id="tk-error-general">{{ $errors->any() ? 'Revisa los campos marcados.' : '' }}</p>

                    {{-- Enviar como: solo para quien pertenece a más de un departamento --}}
                    @if ($misRoles->count() > 1)
                    <div class="row section">
                        <div class="col s12">
                            <h6><strong>Enviar como</strong></h6>
                        </div>
                        @foreach ($misRoles as $rol)
                        <div class="col s6 m4">
                            <p>
                                <label>
                                    <input class="with-gap" type="radio" name="rol_origen_id" value="{{ $rol->id }}" {{ old('rol_origen_id') == $rol->id ? 'checked' : '' }}>
                                    <span>{{ $rol->name }}</span>
                                </label>
                            </p>
                        </div>
                        @endforeach
                        <div class="col s12">
                            <span class="red-text text-darken-2 tk-error" data-error="rol_origen_id">{{ $errors->first('rol_origen_id') }}</span>
                        </div>
                    </div>
                    @endif

                    <div class="row section">
                        <div class="col s12">
                            <h6><strong>¿A qué departamento?</strong></h6>
                        </div>
                        @foreach ($destinos as $rol)
                            {{-- Con un solo departamento propio, no se ofrece enviarse a sí mismo --}}
                            @continue($misRoles->count() === 1 && $misRoles->first()->id == $rol->id)
                        <div class="col s6 m4" data-rol="{{ $rol->id }}">
                            <p>
                                <label>
                                    <input class="with-gap" type="radio" name="rol_destino_id" value="{{ $rol->id }}" {{ old('rol_destino_id') == $rol->id ? 'checked' : '' }}>
                                    <span>{{ $rol->name }}</span>
                                </label>
                            </p>
                        </div>
                        @endforeach
                        <div class="col s12">
                            <span class="red-text text-darken-2 tk-error" data-error="rol_destino_id">{{ $errors->first('rol_destino_id') }}</span>
                        </div>
                    </div>

                    <div class="row section">
                        <div class="input-field col s12">
                            <input type="text" id="titulo" name="titulo" maxlength="150" value="{{ old('titulo') }}"
                                placeholder="Ej: Fuga de agua en baño de hombres" autocomplete="off">
                            <label for="titulo" class="active">¿Qué pasa?</label>
                            <span class="red-text text-darken-2 tk-error" data-error="titulo">{{ $errors->first('titulo') }}</span>
                        </div>

                        <div class="input-field col s12">
                            <textarea id="descripcion" name="descripcion" class="materialize-textarea" maxlength="5000"
                                placeholder="Lo que ayude a resolverlo">{{ old('descripcion') }}</textarea>
                            <label for="descripcion" class="active">Más detalles (opcional)</label>
                            <span class="red-text text-darken-2 tk-error" data-error="descripcion">{{ $errors->first('descripcion') }}</span>
                        </div>
                    </div>

                    <div class="row section">
                        <div class="col s12">
                            <h6><strong>Prioridad</strong></h6>
                        </div>
                        @php
                            $colores = ['baja' => 'black-text', 'normal' => 'cyan-text text-darken-2', 'alta' => 'orange-text text-darken-2', 'urgente' => 'red-text'];
                        @endphp
                        @foreach (array_reverse($prioridades, true) as $clave => $etiqueta)
                        <div class="col s6 m3">
                            <p>
                                <label>
                                    <input class="with-gap" type="radio" name="prioridad" value="{{ $clave }}" {{ old('prioridad', 'normal') === $clave ? 'checked' : '' }}>
                                    <span class="{{ $colores[$clave] ?? '' }}">{{ $etiqueta }}</span>
                                </label>
                            </p>
                        </div>
                        @endforeach
                        <div class="col s12">
                            <span class="red-text text-darken-2 tk-error" data-error="prioridad">{{ $errors->first('prioridad') }}</span>
                        </div>
                    </div>

                    <div class="row section">
                        <div class="input-field col s12">
                            <i class="material-icons prefix">place</i>
                            <input type="text" id="ubicacion" name="ubicacion" maxlength="150" value="{{ old('ubicacion') }}"
                                placeholder="Ej: Quillay 2, caldera, bodega" autocomplete="off"
                                role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="tk-sugerencias">
                            <label for="ubicacion" class="active">¿Dónde? (opcional)</label>
                            <ul class="autocomplete-content dropdown-content" id="tk-sugerencias" role="listbox" style="display:none;"></ul>
                            <span class="red-text text-darken-2 tk-error" data-error="ubicacion">{{ $errors->first('ubicacion') }}</span>
                        </div>
                    </div>

                    @include('themes.backoffice.pages.tickets.partials.selector-fotos', [
                        'prefijo' => 'tk', 'max' => $maxFotos,
                        'error' => $errors->first('fotos') ?: $errors->first('fotos.*'),
                    ])

                    <button type="submit" class="btn-large waves-effect waves-light cyan darken-2 btn-block" id="tk-enviar">
                        <i class="material-icons left">send</i>Enviar ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('foot')
@include('themes.backoffice.pages.tickets.partials.selector-fotos-recursos')
<script>
(function () {
    var form = document.getElementById('tk-form'),
        btn = document.getElementById('tk-enviar'),
        selectorFotos = TkFotos.iniciar(form.querySelector('.tk-selector-fotos'));

    // ── "Enviar como": oculta ese mismo departamento de los destinos ──
    function actualizarDestinos() {
        var origen = form.querySelector('input[name=rol_origen_id]:checked');
        form.querySelectorAll('[data-rol]').forEach(function (col) {
            var mismo = origen && col.getAttribute('data-rol') === origen.value;
            col.classList.toggle('hide', !!mismo);
            if (mismo) col.querySelector('input').checked = false;
        });
    }
    form.querySelectorAll('input[name=rol_origen_id]').forEach(function (r) { r.addEventListener('change', actualizarDestinos); });
    actualizarDestinos();

    // ── Ubicación: texto libre; mientras se escribe sugiere lugares conocidos ──
    (function () {
        var MAX_SUGERENCIAS = 6;
        var input = document.getElementById('ubicacion'), lista = document.getElementById('tk-sugerencias');
        var actuales = [], activa = -1;

        // Sin tildes ni mayúsculas; conserva el largo (texto en NFC) para poder resaltar la coincidencia
        function normalizar(s) { return s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); }
        function escapar(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

        var lugares = @json($sugerencias).map(function (texto, orden) {
            texto = texto.normalize('NFC');
            return { texto: texto, norm: normalizar(texto), orden: orden };
        });

        function buscar(consulta) {
            var q = normalizar(consulta.trim());
            if (!q) return [];
            var encontrados = [];
            lugares.forEach(function (l) {
                var pos = l.norm.indexOf(q);
                if (pos < 0 || l.norm === q) return; // ya escrito completo: no hace falta sugerir
                // Primero los que empiezan así, luego los que tienen una palabra que empieza así
                var rango = pos === 0 ? 0 : (/[\s\-\/(]/.test(l.norm.charAt(pos - 1)) ? 1 : 2);
                encontrados.push({ lugar: l, pos: pos, largo: q.length, rango: rango });
            });
            encontrados.sort(function (a, b) { return a.rango - b.rango || a.lugar.orden - b.lugar.orden; });
            return encontrados.slice(0, MAX_SUGERENCIAS);
        }

        function cerrar() {
            lista.style.display = 'none'; lista.innerHTML = ''; actuales = []; activa = -1;
            input.setAttribute('aria-expanded', 'false');
        }

        function marcar(i) {
            activa = i;
            Array.prototype.forEach.call(lista.children, function (li, j) { li.classList.toggle('active', j === i); });
            if (lista.children[i]) lista.children[i].scrollIntoView({ block: 'nearest' });
        }

        function elegir(i) {
            if (!actuales[i]) return;
            input.value = actuales[i].lugar.texto;
            cerrar();
        }

        function abrir() {
            actuales = buscar(input.value);
            if (!actuales.length) { cerrar(); return; }
            lista.innerHTML = actuales.map(function (r, i) {
                var t = r.lugar.texto;
                return '<li role="option" data-i="' + i + '"><span>'
                    + escapar(t.slice(0, r.pos)) + '<span class="highlight">' + escapar(t.slice(r.pos, r.pos + r.largo)) + '</span>'
                    + escapar(t.slice(r.pos + r.largo)) + '</span></li>';
            }).join('');
            lista.style.display = 'block'; activa = -1;
            input.setAttribute('aria-expanded', 'true');
        }

        input.addEventListener('input', abrir);
        input.addEventListener('focus', function () { if (input.value) abrir(); });
        input.addEventListener('blur', function () { setTimeout(cerrar, 150); });
        input.addEventListener('keydown', function (e) {
            if (lista.style.display === 'none') return;
            if (e.key === 'ArrowDown') { e.preventDefault(); marcar(Math.min(activa + 1, actuales.length - 1)); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); marcar(Math.max(activa - 1, 0)); }
            else if (e.key === 'Escape') { cerrar(); }
            else if (e.key === 'Enter') { e.preventDefault(); activa >= 0 ? elegir(activa) : cerrar(); }
        });
        // mousedown (también lo dispara el toque) evita que el input pierda el foco antes del clic
        lista.addEventListener('mousedown', function (e) { e.preventDefault(); });
        lista.addEventListener('click', function (e) {
            var li = e.target.closest('li');
            if (li) elegir(parseInt(li.getAttribute('data-i'), 10));
        });
    })();

    // ── Errores ──
    function limpiarErrores() {
        document.querySelectorAll('.tk-error').forEach(function (e) { e.textContent = ''; });
    }
    function mostrarError(campo, texto) {
        var clave = campo.indexOf('fotos') === 0 ? 'fotos' : campo;
        var el = document.querySelector('.tk-error[data-error="' + clave + '"]');
        if (el && !el.textContent) el.textContent = texto;
    }

    // ── Envío: fetch para no perder las fotos si falta algún dato ──
    var enviando = false;
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (enviando) return;
        enviando = true; btn.disabled = true; btn.innerHTML = '<i class="material-icons left">hourglass_empty</i>Enviando…';
        limpiarErrores();

        TkFotos.enviar(form, selectorFotos).then(function (r) {
            if (r.status === 200 && r.json.redirect) { window.location.href = r.json.redirect; return; }
            if (r.status === 422 && r.json.errors) {
                Object.keys(r.json.errors).forEach(function (campo) { mostrarError(campo, r.json.errors[campo][0]); });
                document.getElementById('tk-error-general').textContent = 'Revisa los campos marcados.';
            } else if (r.status === 419) {
                document.getElementById('tk-error-general').textContent = 'La sesión expiró. Recarga la página e inténtalo de nuevo.';
            } else {
                document.getElementById('tk-error-general').textContent = 'No se pudo enviar el ticket. Inténtalo de nuevo.';
            }
            var primero = document.querySelector('.tk-error:not(:empty)');
            if (primero) primero.scrollIntoView({ behavior: 'smooth', block: 'center' });
            throw null;
        }).catch(function (err) {
            if (err) document.getElementById('tk-error-general').textContent = 'Sin conexión. Revisa tu internet e inténtalo de nuevo.';
            enviando = false; btn.disabled = false; btn.innerHTML = '<i class="material-icons left">send</i>Enviar ticket';
        });
    });
})();
</script>
@endsection
