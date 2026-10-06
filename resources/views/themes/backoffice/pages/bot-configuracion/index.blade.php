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
                        @php($ini = mb_strtoupper(mb_substr(trim($p->nombre ?: 'C'), 0, 1)))
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
    </div>

    <form action="{{ route('backoffice.bot-configuracion.secciones.update') }}" method="POST">
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
@endsection
