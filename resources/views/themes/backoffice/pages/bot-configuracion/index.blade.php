@extends('themes.backoffice.layouts.admin')

@section('title', 'Configuración del Bot')

@section('head')
<style>
    .bc-switch { position:relative; display:inline-block; width:52px; height:28px; margin:0; }
    .bc-switch input { opacity:0; width:0; height:0; position:absolute; }
    .bc-slider { position:absolute; cursor:pointer; inset:0; background:#ccc; border-radius:28px; transition:.2s; }
    .bc-slider:before { content:""; position:absolute; height:22px; width:22px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.2s; }
    .bc-switch input:checked + .bc-slider { background:#16a34a; }
    .bc-switch input:checked + .bc-slider:before { transform:translateX(24px); }

</style>
<style>
    .bc-tabs { display:flex; border-bottom:2px solid #e0e0e0; margin-bottom:20px; flex-wrap:wrap; }
    .bc-tab-btn {
        padding:12px 20px; cursor:pointer; font-weight:600; color:#757575;
        border-bottom:3px solid transparent; background:none; border-top:none; border-left:none; border-right:none;
    }
    .bc-tab-btn.active { color:#3B82F6; border-bottom-color:#3B82F6; }
    .bc-panel { display:none; }
    .bc-panel.active { display:block; }
    .bc-card { background:#fff; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,.15); padding:20px; margin-bottom:18px; }
    .bc-card h6 { margin-top:0; font-weight:700; color:#333; }
    .bc-card textarea { width:100%; min-height:110px; font-family:inherit; padding:8px; border:1px solid #ccc; border-radius:4px; }
    .bc-lock { color:#f57c00; font-size:13px; margin-bottom:6px; display:block; }
    .bc-masaje-cat { font-weight:700; margin:14px 0 6px; color:#3B82F6; }
    .bc-masaje-row { padding:4px 0; border-bottom:1px solid #f0f0f0; }
    .bc-banner { background:#e3f2fd; border-left:4px solid #3B82F6; padding:12px 16px; border-radius:4px; margin-bottom:16px; }
</style>
@endsection

@section('breadcrumbs')
<li>Configuración del Bot</li>
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Configuración del Bot de WhatsApp</strong></p>
    <div class="divider"></div>

        {{-- ===== Estado del bot y pendientes ===== --}}
    <div style="border:1px solid #e0e0e0;border-radius:8px;padding:16px 20px;margin-bottom:20px;background:#fafafa;">
        <form action="{{ route('backoffice.bot-configuracion.estado.update') }}" method="POST" id="formEstadoBot">
            @csrf
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                <strong style="font-size:1.1rem;">Bot de WhatsApp</strong>
                <label class="bc-switch">
                    <input type="checkbox" name="bot_activo" value="1" {{ $botActivo ? 'checked' : '' }}
                           onchange="if(confirm(this.checked ? '¿Activar el bot?' : '¿Desactivar el bot? Los mensajes quedarán pendientes y recibirás una alerta.')) { document.getElementById('formEstadoBot').submit(); } else { this.checked = !this.checked; }">
                    <span class="bc-slider"></span>
                </label>
                <span style="font-weight:600;color:{{ $botActivo ? '#16a34a' : '#dc2626' }};">
                    {{ $botActivo ? 'Activo: respondiendo mensajes' : 'Desactivado: los mensajes quedan pendientes' }}
                </span>
            </div>
            <details style="margin-top:10px;">
                <summary style="cursor:pointer;color:#555;">Alertas cuando el bot está desactivado</summary>
                <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:10px;align-items:flex-end;">
                    <div>
                        <label>Correo de alerta</label>
                        <input type="email" name="alerta_correo" value="{{ $alertaCorreo }}" style="max-width:260px;">
                    </div>
                    <div>
                        <label>WhatsApp de alerta (ej. 56912345678)</label>
                        <input type="text" name="alerta_telefono" value="{{ $alertaTelefono }}" style="max-width:200px;">
                    </div>
                    <button type="submit" class="btn btn-small">Guardar alertas</button>
                </div>
                <p style="color:#777;font-size:.85rem;margin-top:6px;">
                    La alerta llega una vez cada 30 minutos por cliente. El aviso por WhatsApp solo llega si ese número le escribió al bot en las últimas 24 horas (regla de Meta); el correo llega siempre.
                </p>
            </details>
        </form>

        @if($pendientes->count())
            <hr style="margin:16px 0;">
            <strong>Conversaciones pendientes ({{ $pendientes->count() }})</strong>
            <p style="color:#777;font-size:.85rem;margin:4px 0 10px;">
                Llegaron mientras el bot estaba desactivado. WhatsApp solo permite responder con el bot dentro de 24 horas desde el último mensaje del cliente; las que están en gris hay que responderlas a mano.
            </p>
            <form action="{{ route('backoffice.bot-configuracion.pendientes.procesar') }}" method="POST">
                @csrf
                <table class="striped" style="font-size:.9rem;">
                    <thead><tr><th></th><th>Cliente</th><th>Mensajes</th><th>Último</th></tr></thead>
                    <tbody>
                    @foreach($pendientes as $p)
                        <tr style="{{ $p->en_ventana ? '' : 'opacity:.55;' }}">
                            <td>
                                <label>
                                    <input type="checkbox" class="filled-in" name="telefonos[]" value="{{ $p->telefono }}" {{ $p->en_ventana ? 'checked' : '' }}>
                                    <span></span>
                                </label>
                            </td>
                            <td>{{ $p->nombre ?: 'Cliente' }}<br><small>+{{ $p->telefono }}</small></td>
                            <td>
                                @foreach($p->mensajes as $m)
                                    <div style="margin-bottom:3px;">• {{ \Illuminate\Support\Str::limit($m->mensaje, 160) }}</div>
                                @endforeach
                            </td>
                            <td>
                                {{ $p->ultimo_at->diffForHumans() }}
                                @unless($p->en_ventana)<br><small style="color:#dc2626;">Más de 24 h: responder a mano</small>@endunless
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap;">
                    <button type="submit" name="accion" value="responder" class="btn" {{ $botActivo ? '' : 'disabled' }}
                            onclick="return confirm('El bot responderá las conversaciones marcadas. ¿Continuar?');">
                        Responder seleccionadas con el bot
                    </button>
                    <button type="submit" name="accion" value="atendido" class="btn btn-flat">
                        Marcar como atendidas (ya respondí)
                    </button>
                </div>
                @unless($botActivo)
                    <p style="color:#777;font-size:.85rem;margin-top:6px;">Activa el bot para poder responder con él.</p>
                @endunless
            </form>
        @endif
    </div>

    <div class="bc-tabs">
        <button type="button" class="bc-tab-btn active" data-tab="perfil">Perfil del vendedor</button>
        <button type="button" class="bc-tab-btn" data-tab="recinto">Info del recinto</button>
        <button type="button" class="bc-tab-btn" data-tab="guion">Guion del bot</button>
        <button type="button" class="bc-tab-btn" data-tab="masajes">Masajes</button>
    </div>

    <form action="{{ route('backoffice.bot-configuracion.secciones.update') }}" method="POST">
        @csrf

        {{-- PERFIL --}}
        <div class="bc-panel active" id="panel-perfil">
            @foreach ($perfil as $s)
                <div class="bc-card">
                    <h6>{{ $s->etiqueta }}</h6>
                    <textarea name="contenido[{{ $s->clave }}]">{{ old('contenido.'.$s->clave, $s->contenido) }}</textarea>
                </div>
            @endforeach
        </div>

        {{-- RECINTO --}}
        <div class="bc-panel" id="panel-recinto">
            @foreach ($recinto as $s)
                <div class="bc-card">
                    <h6>{{ $s->etiqueta }}</h6>
                    <textarea name="contenido[{{ $s->clave }}]">{{ old('contenido.'.$s->clave, $s->contenido) }}</textarea>
                </div>
            @endforeach
        </div>

        <div id="btn-guardar-secciones" style="text-align:right; margin-bottom:30px;">
            <button type="submit" class="btn waves-effect waves-light">
                Guardar cambios
                <i class="material-icons right">save</i>
            </button>
        </div>
    </form>

    {{-- GUION --}}
    <div class="bc-panel" id="panel-guion">
        <form action="{{ route('backoffice.bot-configuracion.pasos.update') }}" method="POST">
            @csrf
            @foreach ($pasos as $p)
                <div class="bc-card">
                    @if ($p->bloqueado)
                        <span class="bc-lock">🔒 Este paso tiene lógica que depende del programa elegido (se calcula en el sistema). Puedes editar el texto, pero la condición no cambia acá.</span>
                    @endif
                    <div class="row" style="margin-bottom:0;">
                        <div class="col s2 m1">
                            <strong>#{{ $p->numero_paso }}</strong>
                        </div>
                        <div class="col s7 m9">
                            <input type="text" name="titulo[{{ $p->id }}]" value="{{ old('titulo.'.$p->id, $p->titulo) }}" style="font-weight:600;">
                        </div>
                        <div class="col s3 m2 right-align">
                            <label>
                                <input type="checkbox" name="activo[]" value="{{ $p->id }}" {{ $p->activo ? 'checked' : '' }} class="filled-in" />
                                <span>Activo</span>
                            </label>
                        </div>
                    </div>
                    <textarea name="instrucciones[{{ $p->id }}]">{{ old('instrucciones.'.$p->id, $p->instrucciones) }}</textarea>
                </div>
            @endforeach
            <div style="text-align:right; margin-bottom:30px;">
                <button type="submit" class="btn waves-effect waves-light">
                    Guardar guion
                    <i class="material-icons right">save</i>
                </button>
            </div>
        </form>
    </div>

    {{-- MASAJES (solo lectura) --}}
    <div class="bc-panel" id="panel-masajes">
        <div class="bc-banner">
            Este catálogo se lee en vivo desde <strong>Masajes → Valores</strong>. No se edita acá para evitar
            tener el precio duplicado en dos lugares. Para cambiar un nombre, precio o duración,
            <a href="{{ route('backoffice.masajes.valores') }}">ve al módulo de Masajes</a>.
        </div>
        @foreach ($masajes as $categoria => $tipos)
            <div class="bc-masaje-cat">{{ $categoria }}</div>
            @foreach ($tipos as $t)
                @foreach ($t->precios as $precio)
                    <div class="bc-masaje-row">
                        {{ $t->nombre }} — {{ $precio->duracion_minutos }} min:
                        ${{ number_format($precio->precio_unitario, 0, ',', '.') }}
                        @if ($precio->precio_pareja)
                            (pareja: ${{ number_format($precio->precio_pareja, 0, ',', '.') }})
                        @endif
                    </div>
                @endforeach
            @endforeach
        @endforeach
        @if ($masajes->isEmpty())
            <p>No hay masajes activos con precio cargado. El bot no ofrecerá masajes adicionales hasta que se cargue al menos uno en el módulo de Masajes.</p>
        @endif
    </div>
</div>
@endsection

@section('foot')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var buttons = document.querySelectorAll('.bc-tab-btn');
    var panels = {
        perfil: document.getElementById('panel-perfil'),
        recinto: document.getElementById('panel-recinto'),
        guion: document.getElementById('panel-guion'),
        masajes: document.getElementById('panel-masajes')
    };
    var saveBtn = document.getElementById('btn-guardar-secciones');

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            buttons.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            Object.keys(panels).forEach(function (key) {
                panels[key].classList.toggle('active', key === btn.dataset.tab);
            });
            saveBtn.style.display = (btn.dataset.tab === 'perfil' || btn.dataset.tab === 'recinto') ? 'block' : 'none';
        });
    });
});
</script>
@endsection
