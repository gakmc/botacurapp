@extends('themes.backoffice.layouts.admin')

@section('title', 'Configuración del Bot')

@section('head')
<style>
/* ===== Tarjeta estado del bot (bcx) ===== */
.bcx-card{--ok:#10b981;--ok2:#059669;--off:#94a3b8;--ink:#0f172a;--mut:#64748b;--line:#e2e8f0;--acc:#3B82F6;
  position:relative;background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px 22px;margin:0 0 24px;
  box-shadow:0 1px 2px rgba(15,23,42,.04),0 8px 24px -12px rgba(15,23,42,.12);overflow:hidden;transition:box-shadow .3s,border-color .3s}
.bcx-card:before{content:"";position:absolute;inset:0 0 auto 0;height:4px;background:linear-gradient(90deg,var(--ok),#34d399,#22d3ee);transition:opacity .4s}
.bcx-card.is-off:before{background:linear-gradient(90deg,#cbd5e1,#94a3b8)}
.bcx-card.is-saving{opacity:.7;pointer-events:none}
.bcx-head{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.bcx-avatar{width:52px;height:52px;border-radius:14px;display:grid;place-items:center;color:#fff;flex:none;
  background:linear-gradient(135deg,#25d366,#128c7e);box-shadow:0 6px 16px -6px rgba(18,140,126,.6);transition:filter .4s}
.bcx-card.is-off .bcx-avatar{filter:grayscale(1) opacity(.7)}
.bcx-info{flex:1;min-width:200px}
.bcx-name{font-size:1.15rem;font-weight:700;color:var(--ink);letter-spacing:-.01em;line-height:1.3}
.bcx-status{display:flex;align-items:center;gap:8px;color:var(--mut);font-size:.92rem;margin-top:2px}
.bcx-dot{width:9px;height:9px;border-radius:50%;background:var(--ok);position:relative;flex:none}
.bcx-card.is-on .bcx-dot:after{content:"";position:absolute;inset:-4px;border-radius:50%;background:var(--ok);opacity:.35;animation:bcxPulse 1.8s ease-out infinite}
.bcx-card.is-off .bcx-dot{background:#f59e0b}
@keyframes bcxPulse{0%{transform:scale(.6);opacity:.5}100%{transform:scale(1.8);opacity:0}}
.bcx-actions{display:flex;align-items:center;gap:12px;margin-left:auto}
/* switch */
.bcx-switch{position:relative;display:inline-block;cursor:pointer;margin:0;font-size:0;line-height:0}
.bcx-switch input{position:absolute!important;opacity:0!important;width:1px;height:1px;pointer-events:none}
.bcx-track{display:block;width:64px;height:36px;border-radius:999px;background:#cbd5e1;position:relative;
  transition:background .35s cubic-bezier(.4,0,.2,1),box-shadow .35s;box-shadow:inset 0 1px 3px rgba(15,23,42,.18)}
.bcx-knob{position:absolute;top:4px;left:4px;width:28px;height:28px;border-radius:50%;background:#fff;
  box-shadow:0 2px 6px rgba(15,23,42,.25);transition:transform .35s cubic-bezier(.34,1.56,.64,1),width .2s}
.bcx-switch input:checked + .bcx-track{background:linear-gradient(135deg,var(--ok),var(--ok2));box-shadow:0 4px 14px -4px rgba(16,185,129,.7)}
.bcx-switch input:checked + .bcx-track .bcx-knob{transform:translateX(28px)}
.bcx-switch:active .bcx-knob{width:34px}
.bcx-switch input:checked + .bcx-track:active .bcx-knob{transform:translateX(22px)}
.bcx-switch input:focus-visible + .bcx-track{outline:3px solid rgba(59,130,246,.4);outline-offset:2px}
/* chips */
.bcx-chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
.bcx-chip{display:inline-flex;align-items:center;padding:5px 12px;border-radius:999px;background:#f1f5f9;color:#475569;font-size:.82rem;font-weight:500}
.bcx-chip.warn{background:#fef3c7;color:#92400e}
/* botones */
.bcx-btn,.bcx-ghost{display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 18px;border-radius:12px;font-size:.92rem;font-weight:600;
  cursor:pointer;text-transform:none;letter-spacing:0;line-height:1;transition:transform .15s,box-shadow .2s,background .2s;font-family:inherit}
.bcx-btn{border:0;color:#fff;background:linear-gradient(135deg,#3B82F6,#2563eb);box-shadow:0 6px 16px -8px rgba(37,99,235,.8)}
.bcx-btn:hover{transform:translateY(-1px);box-shadow:0 10px 20px -8px rgba(37,99,235,.8)}
.bcx-btn:disabled{background:#cbd5e1;box-shadow:none;cursor:not-allowed;transform:none}
.bcx-ghost{border:1px solid var(--line);background:#fff;color:#334155}
.bcx-ghost:hover{background:#f8fafc;border-color:#cbd5e1}
.bcx-ghost[aria-expanded="true"]{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
.bcx-btn:focus,.bcx-ghost:focus{background-color:inherit}
.bcx-btn:focus{background:linear-gradient(135deg,#3B82F6,#2563eb)}
/* panel alertas */
.bcx-alerts{display:grid;grid-template-rows:0fr;transition:grid-template-rows .35s ease}
.bcx-alerts.open{grid-template-rows:1fr}
.bcx-alerts-inner{overflow:hidden;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end}
.bcx-alerts.open .bcx-alerts-inner{padding-top:16px;margin-top:16px;border-top:1px dashed var(--line)}
.bcx-field{display:flex;flex-direction:column;gap:6px;flex:1;min-width:200px;max-width:300px}
.bcx-field span{font-size:.8rem;font-weight:600;color:var(--mut)}
.bcx-field input{height:40px;border:1px solid var(--line);border-radius:12px;padding:0 14px;font-size:.95rem;color:var(--ink);background:#f8fafc;
  outline:none;transition:border-color .2s,box-shadow .2s,background .2s;box-sizing:border-box;width:100%}
.bcx-field input:focus{border-color:var(--acc);background:#fff;box-shadow:0 0 0 4px rgba(59,130,246,.15)}
.bcx-note{flex-basis:100%;color:var(--mut);font-size:.82rem;line-height:1.45;margin:2px 0 0}
/* pendientes */
.bcx-pend{margin-top:20px;padding-top:18px;border-top:1px solid var(--line)}
.bcx-pend-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:12px}
.bcx-pend-title{font-weight:700;color:var(--ink);font-size:1rem}
.bcx-list{display:flex;flex-direction:column;gap:10px}
.bcx-item{display:flex;gap:12px;align-items:flex-start;padding:14px;border:1px solid var(--line);border-radius:14px;cursor:pointer;
  transition:border-color .2s,background .2s,transform .15s;margin:0;font-size:inherit;color:inherit}
.bcx-item:hover{border-color:#cbd5e1;transform:translateY(-1px)}
.bcx-item.is-sel{border-color:#93c5fd;background:#f8fbff}
.bcx-item.is-old{opacity:.65}
.bcx-check{display:inline-flex;align-items:center;gap:8px;cursor:pointer;margin:0;padding-top:10px;font-size:.85rem;color:var(--mut)}
.bcx-all{padding-top:0;white-space:nowrap}
.bcx-check input{position:absolute!important;opacity:0!important;pointer-events:none;width:1px;height:1px}
.bcx-check i{width:20px;height:20px;border-radius:6px;border:2px solid #cbd5e1;display:grid;place-items:center;transition:all .2s;flex:none;background:#fff}
.bcx-check input:checked + i{background:var(--acc);border-color:var(--acc)}
.bcx-check input:checked + i:after{content:"";width:5px;height:10px;border:solid #fff;border-width:0 2px 2px 0;transform:rotate(45deg) translate(-1px,-1px)}
.bcx-ini{width:40px;height:40px;border-radius:50%;flex:none;display:grid;place-items:center;font-weight:700;color:#fff;
  background:linear-gradient(135deg,hsl(var(--h),70%,60%),hsl(calc(var(--h) + 40),70%,48%))}
.bcx-body{flex:1;min-width:0;display:flex;flex-direction:column;gap:6px}
.bcx-row{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;color:var(--ink)}
.bcx-row small{color:var(--mut)}
.bcx-time{margin-left:auto;font-style:normal;font-size:.8rem;color:var(--mut)}
.bcx-bubble{align-self:flex-start;max-width:100%;background:#f1f5f9;color:#1e293b;padding:8px 12px;border-radius:4px 14px 14px 14px;font-size:.9rem;line-height:1.4;word-wrap:break-word}
.bcx-more{color:var(--mut);font-size:.78rem}
.bcx-badge{align-self:flex-start;background:#fee2e2;color:#b91c1c;font-size:.75rem;font-weight:600;padding:3px 10px;border-radius:999px}
.bcx-foot{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:14px;position:sticky;bottom:0;background:linear-gradient(transparent,#fff 30%);padding-top:10px}
.bcx-foot .bcx-note{flex-basis:auto}
/* modal */
.bcx-modal{position:fixed;inset:0;z-index:2000;display:grid;place-items:center;background:rgba(15,23,42,.45);backdrop-filter:blur(4px);
  opacity:0;pointer-events:none;transition:opacity .25s}
.bcx-modal.open{opacity:1;pointer-events:auto}
.bcx-modal-card{background:#fff;border-radius:20px;padding:24px;width:min(420px,calc(100vw - 32px));box-shadow:0 24px 60px -12px rgba(15,23,42,.35);
  transform:translateY(12px) scale(.97);transition:transform .3s cubic-bezier(.34,1.56,.64,1)}
.bcx-modal.open .bcx-modal-card{transform:none}
.bcx-modal-title{font-size:1.15rem;font-weight:700;color:#0f172a;margin-bottom:6px}
.bcx-modal-text{color:#475569;font-size:.95rem;line-height:1.5}
.bcx-modal-btns{display:flex;justify-content:flex-end;gap:10px;margin-top:22px}
@media (max-width:600px){.bcx-actions{margin-left:0;width:100%;justify-content:space-between}.bcx-card{padding:18px 16px}}
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
    <div class="bcx-card {{ $botActivo ? 'is-on' : 'is-off' }}" id="bcxCard">
        <form action="{{ route('backoffice.bot-configuracion.estado.update') }}" method="POST" id="formEstadoBot">
            @csrf
            <div class="bcx-head">
                <div class="bcx-avatar">
                    <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm5.8 14.03c-.24.68-1.42 1.31-1.96 1.36-.5.05-1.13.07-1.82-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.79-4.17-4.94-4.36-.14-.19-1.18-1.57-1.18-3s.75-2.13 1.02-2.42c.26-.29.58-.36.77-.36h.55c.18 0 .42-.07.65.5.24.58.82 2 .89 2.15.07.14.12.31.02.5-.1.19-.14.31-.29.48-.14.17-.3.37-.43.5-.14.14-.29.3-.13.59.17.29.74 1.22 1.59 1.98 1.09.97 2.01 1.27 2.3 1.42.29.14.46.12.62-.07.17-.19.72-.84.91-1.13.19-.29.38-.24.65-.14.26.1 1.68.79 1.97.94.29.14.48.22.55.34.07.12.07.7-.17 1.38z"/></svg>
                </div>
                <div class="bcx-info">
                    <div class="bcx-name">Bot de WhatsApp</div>
                    <div class="bcx-status">
                        <span class="bcx-dot"></span>
                        <span>{{ $botActivo ? 'En línea · respondiendo mensajes' : 'En pausa · los mensajes quedan pendientes' }}</span>
                    </div>
                </div>
                <div class="bcx-actions">
                    <button type="button" class="bcx-ghost" id="bcxAlertBtn" aria-expanded="false">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span>Alertas</span>
                    </button>
                    <label class="bcx-switch" title="{{ $botActivo ? 'Pausar bot' : 'Activar bot' }}">
                        <input type="checkbox" name="bot_activo" value="1" id="bcxToggle" {{ $botActivo ? 'checked' : '' }}>
                        <i class="bcx-track"><i class="bcx-knob"></i></i>
                    </label>
                </div>
            </div>

            <div class="bcx-chips">
                <span class="bcx-chip {{ $pendientes->count() ? 'warn' : '' }}">
                    {{ $pendientes->count() ? $pendientes->count().' '.($pendientes->count() === 1 ? 'conversación pendiente' : 'conversaciones pendientes') : 'Sin pendientes' }}
                </span>
                <span class="bcx-chip">Alertas a {{ $alertaCorreo ?: 'sin correo' }}{{ $alertaTelefono ? ' · +'.$alertaTelefono : '' }}</span>
            </div>

            <div class="bcx-alerts" id="bcxAlerts">
                <div class="bcx-alerts-inner">
                    <div class="bcx-field">
                        <span>Correo de alerta</span>
                        <input type="email" class="browser-default" name="alerta_correo" value="{{ $alertaCorreo }}" placeholder="hola@botacura.cl">
                    </div>
                    <div class="bcx-field">
                        <span>WhatsApp de alerta</span>
                        <input type="text" class="browser-default" name="alerta_telefono" value="{{ $alertaTelefono }}" placeholder="56912345678">
                    </div>
                    <button type="submit" class="bcx-btn">Guardar</button>
                    <p class="bcx-note">Llega una alerta cada 30 minutos por cliente mientras el bot está en pausa. El correo llega siempre; el WhatsApp solo si ese número le escribió al bot en las últimas 24 horas (regla de Meta).</p>
                </div>
            </div>
        </form>

        @if($pendientes->count())
            <form action="{{ route('backoffice.bot-configuracion.pendientes.procesar') }}" method="POST" id="bcxPendForm" class="bcx-pend">
                @csrf
                <div class="bcx-pend-head">
                    <div>
                        <div class="bcx-pend-title">Conversaciones pendientes</div>
                        <div class="bcx-note">Llegaron con el bot en pausa. El bot solo puede responder dentro de 24 horas desde el último mensaje del cliente.</div>
                    </div>
                    <label class="bcx-check bcx-all"><input type="checkbox" id="bcxAll"><i></i><span>Todas</span></label>
                </div>

                <div class="bcx-list">
                    @foreach($pendientes as $p)
                        <?php $ini = mb_strtoupper(mb_substr(trim($p->nombre ?: 'C'), 0, 1)); ?>
                        <label class="bcx-item {{ $p->en_ventana ? '' : 'is-old' }}">
                            <span class="bcx-check"><input type="checkbox" name="telefonos[]" value="{{ $p->telefono }}" {{ $p->en_ventana ? 'checked' : '' }}><i></i></span>
                            <span class="bcx-ini" style="--h:{{ crc32($p->telefono) % 360 }}">{{ $ini }}</span>
                            <span class="bcx-body">
                                <span class="bcx-row">
                                    <strong>{{ $p->nombre ?: 'Cliente' }}</strong>
                                    <small>+{{ $p->telefono }}</small>
                                    <em class="bcx-time">{{ $p->ultimo_at->locale('es')->diffForHumans() }}</em>
                                </span>
                                @foreach($p->mensajes->take(-3) as $m)
                                    <span class="bcx-bubble">{{ \Illuminate\Support\Str::limit($m->mensaje, 180) }}</span>
                                @endforeach
                                @if($p->mensajes->count() > 3)
                                    <small class="bcx-more">+{{ $p->mensajes->count() - 3 }} mensajes anteriores</small>
                                @endif
                                @unless($p->en_ventana)
                                    <span class="bcx-badge">Más de 24 h · responder desde WhatsApp</span>
                                @endunless
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="bcx-foot">
                    <button type="submit" name="accion" value="responder" class="bcx-btn" id="bcxResponder" {{ $botActivo ? '' : 'disabled' }}>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7z"/></svg>
                        Responder con el bot <b id="bcxCount"></b>
                    </button>
                    <button type="submit" name="accion" value="atendido" class="bcx-ghost" id="bcxAtendido">Marcar como atendidas</button>
                    @unless($botActivo)<span class="bcx-note">Activa el bot para responder con él.</span>@endunless
                </div>
            </form>
        @endif
    </div>

    <div class="bcx-modal" id="bcxModal" aria-hidden="true">
        <div class="bcx-modal-card">
            <div class="bcx-modal-title" id="bcxModalTitle"></div>
            <div class="bcx-modal-text" id="bcxModalText"></div>
            <div class="bcx-modal-btns">
                <button type="button" class="bcx-ghost" id="bcxModalNo">Cancelar</button>
                <button type="button" class="bcx-btn" id="bcxModalYes">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var modal = document.getElementById('bcxModal'), onYes = null;
        function ask(title, text, yesLabel, cb) {
            document.getElementById('bcxModalTitle').textContent = title;
            document.getElementById('bcxModalText').textContent = text;
            document.getElementById('bcxModalYes').textContent = yesLabel;
            onYes = cb; modal.classList.add('open');
        }
        function close(ok) { modal.classList.remove('open'); var f = onYes; onYes = null; if (f) f(ok); }
        document.getElementById('bcxModalYes').onclick = function () { close(true); };
        document.getElementById('bcxModalNo').onclick = function () { close(false); };
        modal.onclick = function (e) { if (e.target === modal) close(false); };

        var tg = document.getElementById('bcxToggle'), card = document.getElementById('bcxCard');
        tg.addEventListener('change', function () {
            var on = tg.checked;
            card.classList.toggle('is-on', on); card.classList.toggle('is-off', !on);
            ask(on ? '¿Activar el bot?' : '¿Pausar el bot?',
                on ? 'El bot vuelve a responder los mensajes nuevos de WhatsApp.' : 'Los mensajes nuevos quedarán pendientes y te llegará una alerta.',
                on ? 'Activar' : 'Pausar',
                function (ok) {
                    if (ok) { card.classList.add('is-saving'); document.getElementById('formEstadoBot').submit(); }
                    else { tg.checked = !on; card.classList.toggle('is-on', !on); card.classList.toggle('is-off', on); }
                });
        });

        var ab = document.getElementById('bcxAlertBtn'), al = document.getElementById('bcxAlerts');
        ab.onclick = function () { var o = al.classList.toggle('open'); ab.setAttribute('aria-expanded', o); };

        var form = document.getElementById('bcxPendForm');
        if (!form) return;
        var boxes = form.querySelectorAll('input[name="telefonos[]"]'), all = document.getElementById('bcxAll'),
            count = document.getElementById('bcxCount'), resp = document.getElementById('bcxResponder');
        function sync() {
            var n = 0; boxes.forEach(function (b) { if (b.checked) n++; b.closest('.bcx-item').classList.toggle('is-sel', b.checked); });
            count.textContent = n ? '(' + n + ')' : ''; all.checked = n === boxes.length;
            return n;
        }
        boxes.forEach(function (b) { b.addEventListener('change', sync); });
        all.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = all.checked; }); sync(); });
        sync();
        var accion = null;
        form.querySelectorAll('button[name="accion"]').forEach(function (b) { b.addEventListener('click', function () { accion = b.value; }); });
        form.addEventListener('submit', function (e) {
            if (form.dataset.ok) return;
            e.preventDefault();
            var n = sync();
            if (!n) { ask('Nada seleccionado', 'Marca al menos una conversación.', 'Entendido', function () {}); return; }
            var r = accion === 'responder';
            ask(r ? 'Responder con el bot' : 'Marcar como atendidas',
                r ? 'El bot responderá ' + n + (n === 1 ? ' conversación' : ' conversaciones') + ', disculpándose por la demora.' : n + (n === 1 ? ' conversación saldrá' : ' conversaciones saldrán') + ' de la lista.',
                r ? 'Responder' : 'Marcar',
                function (ok) {
                    if (!ok) return;
                    var h = document.createElement('input'); h.type = 'hidden'; h.name = 'accion'; h.value = accion; form.appendChild(h);
                    form.dataset.ok = 1; card.classList.add('is-saving'); form.submit();
                });
        });
    })();
    </script>

    <div class="bc-tabs">
        <button type="button" class="bc-tab-btn active" data-tab="perfil">Perfil del vendedor</button>
        <button type="button" class="bc-tab-btn" data-tab="recinto">Info del recinto</button>
        <button type="button" class="bc-tab-btn" data-tab="guion">Guion del bot</button>
        <button type="button" class="bc-tab-btn" data-tab="masajes">Masajes</button>
        <button type="button" class="bc-tab-btn" data-tab="reglas">Reglas comerciales</button>
        <button type="button" class="bc-tab-btn" data-tab="parametros">Parámetros</button>
        <button type="button" class="bc-tab-btn" data-tab="ubicaciones">Ubicaciones</button>
        <button type="button" class="bc-tab-btn" data-tab="aprendizajes">Aprendizajes</button>
        <button type="button" class="bc-tab-btn" data-tab="menu">Menú</button>
    </div>

    <form action="{{ route('backoffice.bot-configuracion.secciones.update') }}" method="POST">
        <div class="bc-panel" id="panel-parametros">
            <p style="color:#555;margin-bottom:16px;">Valores que el sistema usa para calcular cobros y aplicar reglas. El bot no calcula montos: los toma de aquí. Los IDs de programas están en el módulo Programas. Si dejas un campo vacío o con un valor inválido, se usa el valor por defecto.</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:14px 22px;">
            @foreach ($parametros as $r)
                <div>
                    <label style="display:block;font-weight:600;color:#333;font-size:.9rem;margin-bottom:6px;">{{ $r->etiqueta }}</label>
                    @if ($r->clave === 'param_feriados')
                        <textarea name="contenido[{{ $r->clave }}]" rows="4" class="browser-default" style="width:100%;height:auto;min-height:96px;border:1px solid #d0d0d0;border-radius:10px;padding:10px;font-size:.95rem;background:#fff;">{{ $r->contenido }}</textarea>
                    @else
                        <input type="text" name="contenido[{{ $r->clave }}]" value="{{ $r->contenido }}" class="browser-default" style="width:100%;height:40px;border:1px solid #d0d0d0;border-radius:10px;padding:0 12px;font-size:.95rem;background:#fff;box-sizing:border-box;">
                    @endif
                </div>
            @endforeach
            </div>
            <button type="submit" class="btn" style="background:#3B82F6;margin-top:18px;">Guardar parámetros</button>
        </div>

        <div class="bc-panel" id="panel-reglas">
            <p style="color:#555;margin-bottom:16px;">Reglas que el bot aplica al cotizar y responder. Los cambios rigen desde el siguiente mensaje. Si dejas un recuadro vacío, el bot vuelve al texto original.</p>
            @foreach ($reglas as $r)
                <div style="margin-bottom:18px;">
                    <label style="display:block;font-weight:600;color:#333;font-size:1rem;margin-bottom:6px;">{{ $r->etiqueta }}</label>
                    <textarea name="contenido[{{ $r->clave }}]" rows="6" style="width:100%;height:auto;min-height:140px;border:1px solid #d0d0d0;border-radius:6px;padding:10px;font-size:.95rem;line-height:1.4;background:#fff;">{{ $r->contenido }}</textarea>
                </div>
            @endforeach
            <button type="submit" class="btn" style="background:#3B82F6;">Guardar reglas</button>
        </div>

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

    <div class="bc-panel" id="panel-ubicaciones">
        <p style="color:#555;margin-bottom:16px;">Fotos que el bot envía por WhatsApp para mostrar las ubicaciones (una por tipo). En los programas Wellness para 2 o 4 personas envía la de terraza y la de reposera para que el cliente elija. Usa fotos horizontales, livianas (máximo {{ ini_get('upload_max_filesize') }} por foto en este servidor).</p>
        <form action="{{ route('backoffice.bot-configuracion.fotos.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:18px;">
                @foreach ($fotos as $tipo => $f)
                    <div style="border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;background:#fff;">
                        <div style="aspect-ratio:4/3;background:#f1f5f9;display:flex;align-items:center;justify-content:center;">
                            @if ($f->url)
                                <img src="{{ $f->url }}" alt="{{ $f->etiqueta }}" style="width:100%;height:100%;object-fit:cover;">
                            @else
                                <span style="color:#94a3b8;font-size:.9rem;">Sin foto</span>
                            @endif
                        </div>
                        <div style="padding:12px 14px;">
                            <div style="font-weight:700;color:#0f172a;margin-bottom:8px;">{{ $f->etiqueta }}</div>
                            <input type="file" name="foto[{{ $tipo }}]" accept="image/jpeg,image/png,image/webp" class="browser-default" style="font-size:.85rem;margin-bottom:10px;width:100%;">
                            <textarea name="texto[{{ $tipo }}]" rows="3" class="browser-default" placeholder="Texto que acompaña la foto" style="width:100%;height:auto;border:1px solid #d0d0d0;border-radius:10px;padding:8px 10px;font-size:.9rem;box-sizing:border-box;">{{ $f->texto }}</textarea>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="submit" class="btn" style="background:#3B82F6;margin-top:18px;">Guardar fotos y textos</button>
        </form>
    </div>

    {{-- ===== Aprendizajes ===== --}}
    <div class="bc-panel" id="panel-aprendizajes">
    @if (!empty($apr))
        <?php
            $m = $apr['metricas'];
            $tot = max(1, $m['total']);
            $etq = \App\Services\BotAprendizajeService::ETAPAS;
            $reglasTxt = ['grupo_grande' => 'Grupo más grande que el máximo', 'agenda_no_abierta' => 'Mes con agenda cerrada', 'fecha_no_habilitada' => 'Día no habilitado',
                'semana_solo_lunes_viernes' => 'Programa S en fin de semana', 'semana_feriado' => 'Programa S en feriado', 'semana_fuera_de_temporada' => 'Programa S fuera de temporada',
                'solo_transferencia' => 'Medio de pago no permitido', 'programa_no_disponible' => 'Programa no disponible', 'fecha_pasada' => 'Fecha pasada', 'minimo_personas' => 'Bajo el mínimo de personas'];
        ?>
        <style>
            .apr-top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}
            .apr-seg{display:inline-flex;background:#f1f5f9;border-radius:12px;padding:4px;gap:2px}
            .apr-seg a{padding:6px 14px;border-radius:9px;color:#475569;font-weight:600;font-size:.85rem}
            .apr-seg a.on{background:#fff;color:#0f172a;box-shadow:0 1px 3px rgba(15,23,42,.12)}
            .apr-kpis{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:18px}
            .apr-kpi{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px 16px}
            .apr-kpi b{display:block;font-size:1.6rem;color:#0f172a;line-height:1.2}
            .apr-kpi span{font-size:.8rem;color:#64748b}
            .apr-kpi.warn b{color:#b45309}
            .apr-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-bottom:18px}
            .apr-box{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px 18px}
            .apr-box h4{font-size:1rem;font-weight:700;color:#0f172a;margin:0 0 12px}
            .apr-bar{display:grid;grid-template-columns:150px 1fr 48px;align-items:center;gap:10px;margin-bottom:8px;font-size:.85rem;color:#334155}
            .apr-bar i{display:block;height:12px;border-radius:6px;background:#3B82F6;min-width:2px}
            .apr-bar i.cut{background:#f59e0b}
            .apr-bar em{font-style:normal;text-align:right;color:#64748b}
            .apr-li{display:flex;justify-content:space-between;gap:10px;padding:6px 0;border-bottom:1px dashed #e2e8f0;font-size:.88rem;color:#334155}
            .apr-li:last-child{border-bottom:0}
            .apr-sug{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px 18px;margin-bottom:12px}
            .apr-tag{display:inline-block;font-size:.72rem;font-weight:700;padding:3px 9px;border-radius:999px;background:#eff6ff;color:#1d4ed8;margin-right:6px;text-transform:uppercase;letter-spacing:.02em}
            .apr-tag.alta{background:#fee2e2;color:#b91c1c}.apr-tag.baja{background:#f1f5f9;color:#475569}.apr-tag.problema{background:#fef3c7;color:#92400e}
            .apr-sug textarea{width:100%;min-height:70px;height:auto;border:1px solid #d0d0d0;border-radius:10px;padding:8px 10px;font-size:.92rem;margin:8px 0;box-sizing:border-box}
            .apr-btn{display:inline-flex;align-items:center;height:36px;padding:0 14px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-weight:600;font-size:.85rem;cursor:pointer;margin-right:6px}
            .apr-btn.pri{background:#3B82F6;border-color:#3B82F6;color:#fff}
            .apr-btn.ok{background:#10b981;border-color:#10b981;color:#fff}
            .apr-link{color:#2563eb;cursor:pointer;font-size:.82rem;margin-right:8px}
            .apr-tabla{width:100%;font-size:.88rem}
            .apr-tabla td,.apr-tabla th{padding:8px 6px;border-bottom:1px solid #f1f5f9;text-align:left}
            .apr-badge{font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:999px;background:#f1f5f9;color:#475569}
            .apr-badge.e4,.apr-badge.e5{background:#d1fae5;color:#065f46}.apr-badge.ab{background:#fef3c7;color:#92400e}.apr-badge.de{background:#ede9fe;color:#5b21b6}
            .apr-modal{position:fixed;inset:0;background:rgba(15,23,42,.45);display:none;align-items:center;justify-content:center;z-index:2000;padding:16px}
            .apr-modal.open{display:flex}
            .apr-modal-card{background:#f8fafc;border-radius:18px;width:min(640px,100%);max-height:85vh;display:flex;flex-direction:column;overflow:hidden}
            .apr-modal-head{padding:14px 18px;background:#fff;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center}
            .apr-chat{padding:16px;overflow:auto;display:flex;flex-direction:column;gap:8px}
            .apr-msg{max-width:85%;padding:8px 12px;border-radius:14px;font-size:.9rem;line-height:1.4;white-space:pre-wrap;word-wrap:break-word}
            .apr-msg.cliente{align-self:flex-start;background:#fff;border:1px solid #e2e8f0}
            .apr-msg.bot{align-self:flex-end;background:#dcf8c6}
            .apr-msg.equipo{align-self:flex-end;background:#dbeafe}
            .apr-msg.sistema{align-self:center;background:transparent;color:#64748b;font-size:.75rem;font-family:monospace;max-width:100%}
        </style>

        <div class="apr-top">
            <div class="apr-seg">
                @foreach ([7, 14, 30, 90] as $d)
                    <a href="{{ route('backoffice.bot-configuracion.index', ['apr_dias' => $d]) }}#aprendizajes" class="{{ $m['dias'] == $d ? 'on' : '' }}">{{ $d }} días</a>
                @endforeach
            </div>
            <form action="{{ route('backoffice.bot-configuracion.aprendizajes.analizar') }}" method="POST" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Analizando… (puede tardar 1 minuto)';">
                @csrf
                <input type="hidden" name="dias" value="{{ min(30, $m['dias']) }}">
                <button type="submit" class="apr-btn pri">Analizar conversaciones con IA</button>
                <span style="font-size:.8rem;color:#64748b;margin-left:6px;">Último análisis: {{ $m['ultimo_analisis'] ? \Carbon\Carbon::parse($m['ultimo_analisis'])->locale('es')->diffForHumans() : 'nunca' }} · automático todos los días 23:40</span>
            </form>
        </div>

        <div class="apr-kpis">
            <div class="apr-kpi"><b>{{ $m['total'] }}</b><span>Conversaciones</span></div>
            <div class="apr-kpi"><b>{{ $m['embudo'][3] }}</b><span>Llegaron a cotización ({{ round($m['embudo'][3] * 100 / $tot) }}%)</span></div>
            <div class="apr-kpi"><b>{{ $m['embudo'][4] }}</b><span>Reservas o cupos apartados ({{ round($m['embudo'][4] * 100 / $tot) }}%)</span></div>
            <div class="apr-kpi"><b>{{ $m['embudo'][5] }}</b><span>Pagadas</span></div>
            <div class="apr-kpi"><b>{{ $m['derivadas'] }}</b><span>Atendidas por el equipo</span></div>
            <div class="apr-kpi {{ $m['abandonadas'] ? 'warn' : '' }}"><b>{{ $m['abandonadas'] }}</b><span>Se cortaron sin reservar</span></div>
        </div>

        <div class="apr-grid">
            <div class="apr-box">
                <h4>Embudo de reservas</h4>
                @foreach ($m['embudo'] as $i => $n)
                    <div class="apr-bar"><span>{{ $etq[$i] }}</span><i style="width:{{ round($n * 100 / $tot) }}%"></i><em>{{ $n }}</em></div>
                @endforeach
            </div>
            <div class="apr-box">
                <h4>Dónde se cortan</h4>
                <?php $maxc = max(1, max($m['cortes'])); ?>
                @if (array_sum($m['cortes']) === 0)
                    <p style="color:#64748b;font-size:.9rem;">Sin conversaciones cortadas en el período.</p>
                @else
                    @foreach ($m['cortes'] as $i => $n)
                        @if ($i < 4)
                            <div class="apr-bar"><span>Después de: {{ $etq[$i] }}</span><i class="cut" style="width:{{ round($n * 100 / $maxc) }}%"></i><em>{{ $n }}</em></div>
                        @endif
                    @endforeach
                @endif
            </div>
            <div class="apr-box">
                <h4>Por qué deriva a una persona</h4>
                @forelse ($m['motivos'] as $mot => $n)
                    <div class="apr-li"><span>{{ $mot }}</span><b>{{ $n }}</b></div>
                @empty
                    <p style="color:#64748b;font-size:.9rem;">Sin derivaciones en el período.</p>
                @endforelse
            </div>
            <div class="apr-box">
                <h4>Reglas que más frenan</h4>
                @forelse ($m['reglas'] as $cod => $n)
                    <div class="apr-li"><span>{{ $reglasTxt[$cod] ?? $cod }}</span><b>{{ $n }}</b></div>
                @empty
                    <p style="color:#64748b;font-size:.9rem;">Ninguna en el período.</p>
                @endforelse
                @if ($m['montos_bloq'] || $m['vacias'] || $m['errores_ia'])
                    <div class="apr-li" style="color:#b45309;"><span>Montos bloqueados / respuestas vacías / errores de IA</span><b>{{ $m['montos_bloq'] }} / {{ $m['vacias'] }} / {{ $m['errores_ia'] }}</b></div>
                @endif
            </div>
        </div>

        <h4 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:6px 0 10px;">Sugerencias por revisar ({{ count($apr['sugeridos']) }})</h4>
        @forelse ($apr['sugeridos'] as $s)
            <div class="apr-sug">
                <span class="apr-tag {{ $s->prioridad }}">{{ $s->prioridad }}</span><span class="apr-tag {{ $s->tipo === 'problema' ? 'problema' : '' }}">{{ $s->tipo }}</span>
                <strong style="color:#0f172a;">{{ $s->titulo }}</strong>
                @if ($s->motivo)<div style="color:#64748b;font-size:.85rem;margin-top:4px;">{{ $s->motivo }}</div>@endif
                <form action="{{ route('backoffice.bot-configuracion.aprendizajes.accion', $s->id) }}" method="POST">
                    @csrf
                    <textarea name="propuesta" class="browser-default">{{ $s->propuesta }}</textarea>
                    @foreach (json_decode($s->evidencia ?: '[]', true) as $cid)
                        <span class="apr-link" data-conv="{{ $cid }}">Ver conversación #{{ $cid }}</span>
                    @endforeach
                    <div style="margin-top:8px;">
                        @if ($s->tipo !== 'problema')
                            <button name="accion" value="aprobar" class="apr-btn ok">Aprobar y aplicar al bot</button>
                        @else
                            <button name="accion" value="descartar" class="apr-btn ok">Revisado</button>
                        @endif
                        <button name="accion" value="descartar" class="apr-btn">Descartar</button>
                    </div>
                </form>
            </div>
        @empty
            <p style="color:#64748b;">No hay sugerencias pendientes. Usa "Analizar conversaciones con IA" o espera el análisis diario.</p>
        @endforelse

        <h4 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:18px 0 10px;">Aprendizajes aplicados al bot ({{ count($apr['aprobados']) }})</h4>
        @foreach ($apr['aprobados'] as $s)
            <div class="apr-sug" style="{{ $s->activo ? '' : 'opacity:.55' }}">
                <strong style="color:#0f172a;">{{ $s->titulo }}</strong> <span class="apr-badge">{{ $s->origen === 'ia' ? 'sugerido por IA' : 'agregado por el equipo' }}</span>
                <form action="{{ route('backoffice.bot-configuracion.aprendizajes.accion', $s->id) }}" method="POST">
                    @csrf
                    <textarea name="propuesta" class="browser-default">{{ $s->propuesta }}</textarea>
                    <button name="accion" value="guardar" class="apr-btn">Guardar cambios</button>
                    <button name="accion" value="{{ $s->activo ? 'desactivar' : 'activar' }}" class="apr-btn">{{ $s->activo ? 'Desactivar' : 'Activar' }}</button>
                    <button name="accion" value="eliminar" class="apr-btn" onclick="return confirm('¿Eliminar este aprendizaje?');">Eliminar</button>
                </form>
            </div>
        @endforeach
        <form action="{{ route('backoffice.bot-configuracion.aprendizajes.nuevo') }}" method="POST" class="apr-sug">
            @csrf
            <strong style="color:#0f172a;">Agregar un aprendizaje a mano</strong>
            <input type="text" name="titulo" class="browser-default" placeholder="Título corto (ej. Estacionamiento para buses)" style="width:100%;height:38px;border:1px solid #d0d0d0;border-radius:10px;padding:0 10px;margin-top:8px;box-sizing:border-box;" required>
            <textarea name="propuesta" class="browser-default" placeholder="Instrucción para el bot (ej. Si preguntan por buses, indica que el estacionamiento recibe minibuses de hasta 20 pasajeros.)" required></textarea>
            <button type="submit" class="apr-btn pri">Agregar</button>
        </form>

        <h4 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:18px 0 10px;">Conversaciones recientes</h4>
        <div class="apr-box" style="overflow-x:auto;">
            <table class="apr-tabla">
                <thead><tr><th>Fecha</th><th>Cliente</th><th>Llegó a</th><th>Estado</th><th>Mensajes</th><th></th></tr></thead>
                <tbody>
                @foreach ($apr['conversaciones'] as $c)
                    <tr>
                        <td>{{ $c->fecha->locale('es')->isoFormat('D MMM HH:mm') }}</td>
                        <td>{{ $c->nombre }}<br><small style="color:#94a3b8;">+{{ $c->telefono }}</small></td>
                        <td><span class="apr-badge e{{ $c->etapa_num }}">{{ $c->etapa }}</span></td>
                        <td>
                            @if ($c->derivada)<span class="apr-badge de">Equipo</span>@endif
                            @if ($c->abandonada)<span class="apr-badge ab">Se cortó</span>@endif
                        </td>
                        <td>{{ $c->turnos }}</td>
                        <td><span class="apr-link" data-conv="{{ $c->id }}">Ver</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="apr-modal" id="aprModal">
            <div class="apr-modal-card">
                <div class="apr-modal-head"><strong id="aprModalTit">Conversación</strong><button type="button" class="apr-btn" id="aprModalCerrar">Cerrar</button></div>
                <div class="apr-chat" id="aprChat"></div>
            </div>
        </div>
    @else
        <p style="color:#64748b;">Los aprendizajes aún no están disponibles (falta correr la migración).</p>
    @endif
    </div>

    {{-- ===== Menú en PDF ===== --}}
    <div class="bc-panel" id="panel-menu">
        <p style="color:#555;margin-bottom:16px;">El bot envía este PDF por WhatsApp cuando se confirma el pago de una reserva con almuerzo, desayuno u once, y también cuando un cliente pide el menú (si está activado abajo). Al subir uno nuevo, reemplaza al anterior de inmediato. Máximo {{ ini_get('upload_max_filesize') }} por archivo en este servidor.</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px;align-items:start;">
            <form action="{{ route('backoffice.bot-configuracion.menu.update') }}" method="POST" enctype="multipart/form-data" style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;">
                @csrf
                <label style="display:block;font-weight:700;color:#0f172a;margin-bottom:6px;">PDF del menú</label>
                <input type="file" name="menu_pdf" accept="application/pdf" class="browser-default" style="width:100%;font-size:.9rem;margin-bottom:6px;">
                <div style="font-size:.82rem;color:#64748b;margin-bottom:14px;">
                    @if ($menu->url)
                        Actual: <a href="{{ $menu->url }}" target="_blank" rel="noopener">abrir PDF</a> · actualizado {{ $menu->actualizado }}{{ $menu->antiguo ? ' (es el archivo antiguo de otoño: sube el nuevo)' : '' }}
                    @else
                        Aún no hay menú cargado.
                    @endif
                </div>
                <label style="display:block;font-weight:600;color:#333;font-size:.9rem;margin-bottom:6px;">Nombre del menú</label>
                <input type="text" name="menu_titulo" value="{{ $menu->titulo }}" class="browser-default" style="width:100%;height:40px;border:1px solid #d0d0d0;border-radius:10px;padding:0 12px;margin-bottom:12px;box-sizing:border-box;">
                <label style="display:block;font-weight:600;color:#333;font-size:.9rem;margin-bottom:6px;">Nombre del archivo que ve el cliente</label>
                <input type="text" name="menu_archivo_nombre" value="{{ $menu->archivo }}" class="browser-default" style="width:100%;height:40px;border:1px solid #d0d0d0;border-radius:10px;padding:0 12px;margin-bottom:12px;box-sizing:border-box;">
                <label style="display:block;font-weight:600;color:#333;font-size:.9rem;margin-bottom:6px;">Texto que acompaña el PDF</label>
                <textarea name="menu_texto" rows="3" class="browser-default" style="width:100%;height:auto;border:1px solid #d0d0d0;border-radius:10px;padding:8px 12px;margin-bottom:12px;box-sizing:border-box;">{{ $menu->texto }}</textarea>
                <label style="display:flex;align-items:center;gap:10px;color:#333;font-size:.9rem;margin-bottom:16px;cursor:pointer;">
                    <input type="checkbox" name="menu_enviar_al_preguntar" value="1" class="browser-default" style="position:static;opacity:1;pointer-events:auto;width:18px;height:18px;" {{ $menu->al_preguntar ? 'checked' : '' }}>
                    Enviar el PDF cuando un cliente pida el menú
                </label>
                <input type="hidden" name="fondos_form" value="1">
                <div style="border-top:1px dashed #e2e8f0;padding-top:14px;margin-bottom:16px;">
                    <div style="font-weight:700;color:#0f172a;margin-bottom:4px;">Fondos que son plato único</div>
                    <div style="font-size:.82rem;color:#64748b;margin-bottom:10px;">Marca los que no llevan acompañamiento (Pastel de Papas y platos vegetarianos). El bot no pedirá acompañamiento para ellos.</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:6px 14px;">
                        @foreach ($menu->fondos as $fo)
                            <label style="display:flex;align-items:center;gap:8px;color:#333;font-size:.88rem;cursor:pointer;">
                                <input type="checkbox" name="fondos_unicos[]" value="{{ $fo->id }}" class="browser-default" style="position:static;opacity:1;pointer-events:auto;width:16px;height:16px;" {{ $fo->unico ? 'checked' : '' }}>
                                {{ $fo->nombre }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <button type="submit" class="btn" style="background:#3B82F6;">Guardar menú</button>
            </form>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;min-height:200px;">
                @if ($menu->url)
                    <iframe src="{{ $menu->url }}" title="Vista previa del menú" style="width:100%;height:560px;border:0;"></iframe>
                @else
                    <div style="padding:40px;text-align:center;color:#94a3b8;">Sin vista previa</div>
                @endif
            </div>
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
<script>
document.querySelectorAll('.bc-tab-btn').forEach(function (b) {
    b.addEventListener('click', function () {
        var p = document.getElementById('panel-reglas');
        if (!p) return;
        if (b.dataset.tab === 'reglas') {
            document.querySelectorAll('.bc-panel').forEach(function (x) { x.classList.toggle('active', x === p); });
            document.querySelectorAll('.bc-tab-btn').forEach(function (x) { x.classList.toggle('active', x === b); });
        } else {
            p.classList.remove('active');
        }
    });
});
</script>
<script>
document.querySelectorAll('.bc-tab-btn').forEach(function (b) {
    b.addEventListener('click', function () {
        var p = document.getElementById('panel-parametros');
        if (!p) return;
        if (b.dataset.tab === 'parametros') {
            document.querySelectorAll('.bc-panel').forEach(function (x) { x.classList.toggle('active', x === p); });
            document.querySelectorAll('.bc-tab-btn').forEach(function (x) { x.classList.toggle('active', x === b); });
        } else {
            p.classList.remove('active');
        }
    });
});
</script>
<script>
document.querySelectorAll('.bc-tab-btn').forEach(function (b) {
    b.addEventListener('click', function () {
        var p = document.getElementById('panel-ubicaciones');
        if (!p) return;
        if (b.dataset.tab === 'ubicaciones') {
            document.querySelectorAll('.bc-panel').forEach(function (x) { x.classList.toggle('active', x === p); });
            document.querySelectorAll('.bc-tab-btn').forEach(function (x) { x.classList.toggle('active', x === b); });
        } else {
            p.classList.remove('active');
        }
    });
});
</script>
<script>
(function () {
    var p = document.getElementById('panel-aprendizajes');
    if (!p) return;
    document.querySelectorAll('.bc-tab-btn').forEach(function (b) {
        b.addEventListener('click', function () {
            if (b.dataset.tab === 'aprendizajes') {
                document.querySelectorAll('.bc-panel').forEach(function (x) { x.classList.toggle('active', x === p); });
                document.querySelectorAll('.bc-tab-btn').forEach(function (x) { x.classList.toggle('active', x === b); });
                if (history.replaceState) history.replaceState(null, '', '#aprendizajes');
            } else {
                p.classList.remove('active');
            }
        });
    });
    if (location.hash === '#aprendizajes') {
        var t = document.querySelector('.bc-tab-btn[data-tab="aprendizajes"]');
        if (t) t.click();
    }
    var modal = document.getElementById('aprModal'), chat = document.getElementById('aprChat');
    if (!modal) return;
    var urlBase = @json(route('backoffice.bot-configuracion.aprendizajes.conversacion', 0));
    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-conv]');
        if (!el) return;
        chat.innerHTML = '<p style="color:#64748b">Cargando…</p>';
        modal.classList.add('open');
        fetch(urlBase.replace(/0$/, el.dataset.conv), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                document.getElementById('aprModalTit').textContent = (d.cliente || 'Conversación') + ' · #' + (d.id || '');
                chat.innerHTML = (d.mensajes || []).map(function (m) {
                    var et = { cliente: 'Cliente', bot: 'Bot', equipo: 'Equipo', sistema: '' }[m.de];
                    return '<div class="apr-msg ' + m.de + '">' + (et ? '<small style="display:block;font-weight:700;color:#64748b;">' + et + '</small>' : '') + esc(m.texto) + '</div>';
                }).join('') || '<p style="color:#64748b">Sin mensajes.</p>';
            })
            .catch(function () { chat.innerHTML = '<p style="color:#b91c1c">No se pudo cargar la conversación.</p>'; });
    });
    document.getElementById('aprModalCerrar').onclick = function () { modal.classList.remove('open'); };
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.classList.remove('open'); });
})();
</script>
<script>
(function () {
    var p = document.getElementById('panel-menu');
    if (!p) return;
    document.querySelectorAll('.bc-tab-btn').forEach(function (b) {
        b.addEventListener('click', function () {
            if (b.dataset.tab === 'menu') {
                document.querySelectorAll('.bc-panel').forEach(function (x) { x.classList.toggle('active', x === p); });
                document.querySelectorAll('.bc-tab-btn').forEach(function (x) { x.classList.toggle('active', x === b); });
                if (history.replaceState) history.replaceState(null, '', '#menu');
            } else {
                p.classList.remove('active');
            }
        });
    });
    if (location.hash === '#menu') {
        var t = document.querySelector('.bc-tab-btn[data-tab="menu"]');
        if (t) t.click();
    }
})();
</script>
@endsection
