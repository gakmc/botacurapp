@extends('themes.backoffice.layouts.admin')

@section('title','Accesos a la auditoría')

@section('head')
@include('themes.backoffice.pages.auditoria.partials.estilos')
@endsection

@section('breadcrumbs')
<li><a href="{{ route('backoffice.auditoria.index') }}">Auditoría</a></li>
<li>Accesos</li>
@endsection

@section('content')
@php($formato = app(\App\Services\AuditoriaService::class))

<div class="section">
    <p class="caption"><strong>Accesos a la auditoría</strong></p>
    <div class="divider"></div>
    <div class="section">
        <div class="row">
            <div class="col s12">

                @if (session('success'))
                    <div class="card-panel green lighten-4 green-text text-darken-4">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="card-panel red lighten-4 red-text text-darken-4">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="card-panel red lighten-4 red-text text-darken-4">{{ $errors->first() }}</div>
                @endif

                <div class="card-panel">
                    <h6><strong>Otorgar acceso</strong></h6>
                    <p class="grey-text">
                        Un <strong>lector</strong> puede ver la auditoría.
                        Un <strong>gestor</strong> además puede otorgar y quitar accesos (de lectores y de otros gestores).
                        Nadie puede quitarse su propio acceso: debe hacerlo otro gestor.
                    </p>

                    @if ($candidatos->isEmpty())
                        <p>Todos los usuarios activos ya tienen acceso.</p>
                    @else
                        <form method="POST" action="{{ route('backoffice.auditoria.autorizar') }}" class="row js-confirmar"
                              data-solo-si-gestor="1"
                              data-titulo="¿Otorgar acceso de gestor?"
                              data-texto="Un gestor podrá ver la auditoría y otorgar o quitar accesos a otras personas, incluidos otros gestores."
                              data-boton="Sí, hacerlo gestor"
                              data-icono="warning">
                            @csrf
                            <div class="col s12 m6">
                                <label for="user_id">Usuario</label>
                                <select name="user_id" id="user_id" class="browser-default" required>
                                    <option value="">Selecciona un usuario…</option>
                                    @foreach ($candidatos as $candidato)
                                        <option value="{{ $candidato->id }}" @if(old('user_id') == $candidato->id) selected @endif>{{ $candidato->name }} ({{ $candidato->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col s12 m3">
                                <label for="nivel">Nivel</label>
                                <select name="nivel" id="nivel" class="browser-default" required>
                                    <option value="lector" @if(old('nivel', 'lector') === 'lector') selected @endif>Lector (solo ver)</option>
                                    <option value="gestor" @if(old('nivel') === 'gestor') selected @endif>Gestor (ver y administrar accesos)</option>
                                </select>
                            </div>
                            <div class="col s12 m3" style="padding-top: 18px;">
                                <button type="submit" class="btn waves-effect waves-light">Otorgar acceso</button>
                            </div>
                        </form>
                    @endif
                </div>

                <div class="card-panel">
                    <h6><strong>Personas con acceso</strong></h6>
                    <table class="striped responsive-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nivel</th>
                                <th>Otorgado por</th>
                                <th>Desde</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($autorizados as $autorizado)
                                <tr>
                                    <td>
                                        {{ optional($autorizado->user)->name }}
                                        @if ($autorizado->user && ! $autorizado->user->activo)
                                            <span class="grey-text">(inactivo)</span>
                                        @endif
                                    </td>
                                    <td>{{ $autorizado->es_gestor() ? 'Gestor' : 'Lector' }}</td>
                                    <td>{{ optional($autorizado->otorgante)->name ?: 'Consola' }}</td>
                                    <td class="auditoria-fecha">{{ $autorizado->otorgado_at ? $formato->fechaHora($autorizado->otorgado_at) : '—' }}</td>
                                    <td>
                                        @if ((int) $autorizado->user_id === (int) auth()->id())
                                            <span class="grey-text" title="Tu acceso solo lo puede quitar otro gestor">(tú)</span>
                                        @else
                                            @if (! $autorizado->es_gestor() && optional($autorizado->user)->activo)
                                                <form method="POST" action="{{ route('backoffice.auditoria.promover', $autorizado) }}" style="display:inline;"
                                                      class="js-confirmar"
                                                      data-titulo="¿Hacer gestor a {{ optional($autorizado->user)->name }}?"
                                                      data-texto="Podrá ver la auditoría y otorgar o quitar accesos a otras personas, incluidos otros gestores."
                                                      data-boton="Sí, hacerlo gestor"
                                                      data-icono="warning">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn-flat">Hacer gestor</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('backoffice.auditoria.revocar', $autorizado) }}" style="display:inline;"
                                                  class="js-confirmar"
                                                  data-titulo="¿Quitar el acceso a {{ optional($autorizado->user)->name }}?"
                                                  data-texto="Dejará de ver la auditoría{{ $autorizado->es_gestor() ? ' y de administrar accesos' : '' }}. La acción queda registrada."
                                                  data-boton="Sí, quitar acceso"
                                                  data-icono="error">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-flat red-text">Quitar acceso</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
<script>
    // Confirmación con SweetAlert2 para los formularios .js-confirmar.
    // Textos en data-*; se usan titleText/text (texto plano) porque incluyen nombres de usuarios.
    document.addEventListener('submit', function (evento) {
        var form = evento.target;

        if (!form.classList || !form.classList.contains('js-confirmar') || form.dataset.confirmado === '1') {
            return;
        }
        // El formulario de otorgar solo confirma cuando el nivel elegido es gestor
        if (form.dataset.soloSiGestor && (!form.nivel || form.nivel.value !== 'gestor')) {
            return;
        }

        evento.preventDefault();

        var enviar = function () {
            form.dataset.confirmado = '1';
            form.submit();
        };

        if (typeof Swal === 'undefined') {
            if (window.confirm(form.dataset.titulo)) {
                enviar();
            }
            return;
        }

        Swal.fire({
            icon: form.dataset.icono || 'warning',
            titleText: form.dataset.titulo,
            text: form.dataset.texto,
            showCancelButton: true,
            confirmButtonText: form.dataset.boton || 'Confirmar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            focusCancel: true,
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                enviar();
            }
        });
    });
</script>
@endsection
