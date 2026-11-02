@extends('themes.backoffice.layouts.admin')

@section('title', 'Conversaciones del bot')

@section('head')
<style>
.apr-top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.apr-seg{display:inline-flex;background:#f1f5f9;border-radius:12px;padding:4px;gap:2px}
.apr-seg a{padding:6px 14px;border-radius:9px;color:#475569;font-weight:600;font-size:.85rem}
.apr-seg a.on{background:#fff;color:#0f172a;box-shadow:0 1px 3px rgba(15,23,42,.12)}
.apr-box{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px 18px}
.apr-tabla{width:100%;font-size:.88rem}
.apr-tabla td,.apr-tabla th{padding:8px 6px;border-bottom:1px solid #f1f5f9;text-align:left}
.apr-badge{font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:999px;background:#f1f5f9;color:#475569}
.apr-badge.e4,.apr-badge.e5{background:#d1fae5;color:#065f46}.apr-badge.ab{background:#fef3c7;color:#92400e}.apr-badge.de{background:#ede9fe;color:#5b21b6}
.apr-link{color:#2563eb;cursor:pointer;font-size:.82rem}
.apr-btn{display:inline-flex;align-items:center;height:36px;padding:0 14px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-weight:600;font-size:.85rem;cursor:pointer}
.apr-modal{position:fixed;inset:0;background:rgba(15,23,42,.45);display:none;align-items:center;justify-content:center;z-index:2000;padding:16px}
.apr-modal.open{display:flex}
.apr-modal-card{background:#f8fafc;border-radius:18px;width:min(640px,100%);max-height:85vh;display:flex;flex-direction:column;overflow:hidden}
.apr-modal-head{padding:14px 18px;background:#fff;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;gap:8px}
.apr-chat{padding:16px;overflow:auto;display:flex;flex-direction:column;gap:8px}
.apr-msg{max-width:85%;padding:8px 12px;border-radius:14px;font-size:.9rem;line-height:1.4;white-space:pre-wrap;word-wrap:break-word}
.apr-msg.cliente{align-self:flex-start;background:#fff;border:1px solid #e2e8f0}
.apr-msg.bot{align-self:flex-end;background:#dcf8c6}
.apr-msg.equipo{align-self:flex-end;background:#dbeafe}
.apr-msg.sistema{align-self:center;background:transparent;color:#64748b;font-size:.75rem;font-family:monospace;max-width:100%}
.dp-box{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px 18px;margin:16px 0}
.dp-box h6{margin:0 0 4px;font-weight:700;font-size:1rem;color:#0f172a}
.dp-dia{border-top:1px solid #f1f5f9;padding:8px 0}
.dp-dia summary{cursor:pointer;display:flex;align-items:center;gap:10px;flex-wrap:wrap;list-style:none}
.dp-dia summary::-webkit-details-marker{display:none}
.dp-fecha{font-weight:700;color:#0f172a;min-width:190px;text-transform:capitalize}
.dp-num{font-size:.85rem;color:#475569}
.dp-badge{font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:999px;background:#f1f5f9;color:#475569}
.dp-badge.evaluar{background:#d1fae5;color:#065f46}.dp-badge.hab{background:#dbeafe;color:#1e40af}
.dp-tabla{width:100%;font-size:.85rem;margin-top:8px}
.dp-tabla td,.dp-tabla th{padding:6px;border-bottom:1px solid #f8fafc;text-align:left;vertical-align:top}
.dp-acc{display:inline-flex;gap:6px;flex-wrap:wrap}
.dp-acc form{display:inline;margin:0}
.dp-mini{height:28px;padding:0 10px;font-size:.78rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#334155;cursor:pointer;display:inline-flex;align-items:center}
.dp-avisar{background:#128c7e;color:#fff;border-color:#128c7e}
.dp-flash{padding:10px 14px;border-radius:10px;margin:12px 0;font-size:.88rem}
.dp-flash.ok{background:#ecfdf5;color:#065f46}.dp-flash.err{background:#fef2f2;color:#991b1b}
</style>
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Conversaciones del bot</strong></p>
    <div class="divider"></div>

    @if (session('interes_msg'))
        <div class="dp-flash {{ session('interes_ok') ? 'ok' : 'err' }}">{{ session('interes_msg') }}</div>
    @endif

    <div class="dp-box">
        <h6>Días pedidos sin habilitar</h6>
        <p class="grey-text" style="font-size:.78rem;margin:0 0 6px">Fechas que la gente consultó al bot y no estaban habilitadas (lunes a miércoles o días cerrados). En verde: más de {{ $umbral }} personas, para evaluar abrir. "Anotados" pidieron que les avisemos.</p>
        @forelse ($diasPedidos as $d)
            <details class="dp-dia">
                <summary>
                    <span class="dp-fecha">{{ $d->etiqueta }}</span>
                    <span class="dp-num">{{ $d->consultas }} {{ $d->consultas == 1 ? 'consulta' : 'consultas' }} · {{ $d->personas }} personas</span>
                    <span class="dp-num">· {{ $d->anotados }} anotados ({{ $d->personas_anotadas }} pers.)</span>
                    @if ($d->evaluar)<span class="dp-badge evaluar">Evaluar abrir</span>@endif
                    @if ($d->habilitada)<span class="dp-badge hab">Ya habilitado</span>@endif
                    @if ($d->avisados)<span class="dp-badge">{{ $d->avisados }} avisados</span>@endif
                    @if ($d->habilitada && $d->por_avisar)
                        <form method="POST" action="{{ route('backoffice.bot-conversaciones.interes.avisar') }}" style="margin:0" onsubmit="return confirm('¿Avisar por WhatsApp a {{ $d->por_avisar }} persona(s) que el {{ $d->etiqueta }} está habilitado?')">
                            @csrf
                            <input type="hidden" name="fecha" value="{{ $d->fecha }}">
                            <button type="submit" class="dp-mini dp-avisar">Avisar a {{ $d->por_avisar }}</button>
                        </form>
                    @endif
                </summary>
                <div style="overflow-x:auto">
                <table class="dp-tabla">
                    <thead><tr><th>Cliente</th><th>Personas</th><th>Programa</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($d->personas_detalle as $p)
                        <tr>
                            <td>{{ $p->nombre ?: 'Sin nombre' }}<br><small style="color:#94a3b8">+{{ $p->telefono }}</small></td>
                            <td>{{ $p->personas ?: '—' }}</td>
                            <td>{{ $p->programa ?: '—' }}</td>
                            <td>
                                @if ($p->estado === 'consulta')<span class="dp-badge">Solo consultó</span>
                                @elseif ($p->estado === 'interesado')<span class="dp-badge evaluar">Pidió aviso</span>
                                @elseif ($p->estado === 'avisado')<span class="dp-badge hab">Avisado{{ $p->avisado_at ? ' ' . \Carbon\Carbon::parse($p->avisado_at)->format('d/m') : '' }}</span>@endif
                                @if ($p->nota)<br><small style="color:#b45309">{{ $p->nota }}</small>@endif
                            </td>
                            <td>
                                <span class="dp-acc">
                                    <a class="dp-mini" target="_blank" style="color:#128c7e"
                                       href="{{ (!empty($p->correo) && strpos($p->telefono, 'm') === 0) ? 'mailto:' . $p->correo . '?subject=' . rawurlencode('Botacura: habilitamos el día que consultaste') . '&body=' . rawurlencode(\App\Services\BotInteresFechasService::textoAviso($p->nombre, $d->fecha)) : 'https://wa.me/' . $p->telefono . '?text=' . rawurlencode(\App\Services\BotInteresFechasService::textoAviso($p->nombre, $d->fecha)) }}">{{ (!empty($p->correo) && strpos($p->telefono, 'm') === 0) ? 'Correo' : 'WhatsApp' }}</a>
                                    @if ($p->estado !== 'avisado')
                                        <form method="POST" action="{{ route('backoffice.bot-conversaciones.interes.estado', $p->id) }}">@csrf<input type="hidden" name="estado" value="avisado"><button class="dp-mini" type="submit">Marcar avisado</button></form>
                                    @endif
                                    <form method="POST" action="{{ route('backoffice.bot-conversaciones.interes.estado', $p->id) }}" onsubmit="return confirm('¿Quitar de la lista?')">@csrf<input type="hidden" name="estado" value="descartado"><button class="dp-mini" type="submit">Quitar</button></form>
                                </span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            </details>
        @empty
            <p style="color:#64748b;margin:6px 0 0">Todavía no hay consultas por días sin habilitar.</p>
        @endforelse
    </div>

    <div class="apr-top" style="margin-top:16px">
        <div class="apr-seg">
            @foreach ([1 => 'Hoy', 7 => '7 días', 14 => '14 días', 30 => '30 días', 90 => '90 días'] as $d => $txt)
                <a href="{{ route('backoffice.bot-conversaciones.index', ['dias' => $d]) }}" class="{{ $dias == $d ? 'on' : '' }}">{{ $txt }}</a>
            @endforeach
        </div>
        <input type="text" id="convBuscar" class="browser-default" placeholder="Buscar nombre o teléfono"
               style="height:38px;border:1px solid #d0d0d0;border-radius:10px;padding:0 12px;min-width:240px">
    </div>

    @php $pausados = \Illuminate\Support\Facades\Schema::hasTable('bot_chats_pausados') ? \Illuminate\Support\Facades\DB::table('bot_chats_pausados')->orderByDesc('created_at')->get()->keyBy('telefono') : collect(); @endphp
    @if(session('success'))<div class="apr-box" style="border-color:#c8e6d6;background:#eef7f2;color:#1b5e45">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="apr-box" style="border-color:#f1dfba;background:#fff8ec;color:#6b4a10">{{ session('error') }}</div>@endif
    <div class="apr-box" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between">
        <div><strong>Bot por conversación</strong><br><small style="color:#64748b">Apagado en {{ $pausados->count() }} chat(s). En un chat apagado el bot no responde y los mensajes quedan en espera para el equipo; el resto de WhatsApp sigue igual.</small>
            @if($pausados->count())<br><small style="color:#64748b">@foreach($pausados as $pz)+{{ $pz->telefono }}{{ $pz->nombre ? ' (' . $pz->nombre . ')' : '' }}@if(!$loop->last) · @endif @endforeach</small>@endif</div>
        <form method="POST" action="{{ route('backoffice.bot-conversaciones.chat_bot') }}" style="display:flex;gap:8px;align-items:center">@csrf
            <input type="hidden" name="accion" value="apagar">
            <input type="text" name="telefono" placeholder="+56 9 …" inputmode="tel" required style="height:36px;border:1px solid #d0d0d0;border-radius:10px;padding:0 10px;width:150px;margin:0">
            <button class="apr-bot-btn off">Apagar bot en este número</button>
        </form>
    </div>
    <style>
        .apr-bot-btn { border:1px solid #cbd5e1; background:#fff; border-radius:999px; padding:5px 12px; font-size:.8rem; cursor:pointer; white-space:nowrap; }
        .apr-bot-btn.on { border-color:#0e5f49; background:#0e5f49; color:#fff; }
        .apr-bot-btn.off { border-color:#a33a12; color:#a33a12; }
        .apr-bot-est { display:inline-block; font-size:.72rem; font-weight:700; padding:2px 8px; border-radius:999px; margin-bottom:4px; }
        .apr-bot-est.si { background:#eaf4ef; color:#0e5f49; } .apr-bot-est.no { background:#fdecea; color:#a33a12; }
    </style>
        {{-- WhatsApp e Instagram en la misma lista --}}
    @php $igActivo = class_exists(\App\Http\Controllers\Api\InstagramWebhookController::class) && \App\Http\Controllers\Api\InstagramWebhookController::botActivo(); @endphp
    <div class="apr-box" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between">
        <div style="display:flex;gap:6px;flex-wrap:wrap" role="group" aria-label="Filtrar por canal">
            <button type="button" class="apr-bot-btn on" data-filtro-canal="">Todos</button>
            <button type="button" class="apr-bot-btn" data-filtro-canal="whatsapp">WhatsApp</button>
            <button type="button" class="apr-bot-btn" data-filtro-canal="instagram">Instagram</button>
        </div>
        <form method="POST" action="{{ route('backoffice.bot-conversaciones.instagram_bot') }}" style="display:flex;gap:8px;align-items:center;margin:0">@csrf
            <span>Bot en Instagram: <strong style="color:{{ $igActivo ? '#0e5f49' : '#a33a12' }}">{{ $igActivo ? 'Encendido' : 'Apagado' }}</strong></span>
            <input type="hidden" name="accion" value="{{ $igActivo ? 'apagar' : 'encender' }}">
            <button class="apr-bot-btn {{ $igActivo ? 'off' : 'on' }}" onclick="return confirm('{{ $igActivo ? '¿Apagar el bot en Instagram? Los mensajes quedarán en espera para el equipo.' : '¿Encender el bot en Instagram? Responderá los mensajes directos que lleguen desde ahora.' }}')">{{ $igActivo ? 'Apagar' : 'Encender' }}</button>
        </form>
    </div>
    <style>
        .apr-canal { display:inline-block; font-size:.68rem; font-weight:700; padding:1px 7px; border-radius:999px; margin-right:4px; }
        .apr-canal.wa { background:#e7f6ee; color:#0e6b45; } .apr-canal.ig { background:#fbe9f1; color:#9b1b52; }
        .apr-bot-btn { border:1px solid #cbd5e1; background:#fff; border-radius:999px; padding:5px 12px; font-size:.8rem; cursor:pointer; white-space:nowrap; }
        .apr-bot-btn.on { border-color:#0e5f49; background:#0e5f49; color:#fff; } .apr-bot-btn.off { border-color:#a33a12; color:#a33a12; }
    </style>
    <div class="apr-box" style="overflow-x:auto;">
        <table class="apr-tabla">
            <thead><tr><th>Fecha</th><th>Cliente</th><th>Llegó a</th><th>Estado</th><th>Mensajes</th><th>Bot</th><th></th></tr></thead>
            <tbody>
            @forelse ($conversaciones as $c)
                <tr data-buscar="{{ mb_strtolower($c->nombre . ' ' . $c->telefono . ' ' . ($c->canal ?? 'whatsapp')) }}" data-canal="{{ $c->canal ?? 'whatsapp' }}">
                    <td>{{ $c->fecha->locale('es')->isoFormat('D MMM HH:mm') }}</td>
                    <td>@if(($c->canal ?? 'whatsapp') === 'instagram')<span class="apr-canal ig">Instagram</span>@else<span class="apr-canal wa">WhatsApp</span>@endif {{ $c->nombre }}<br><small style="color:#94a3b8;">@if(($c->canal ?? 'whatsapp') === 'instagram') Mensaje directo @else +{{ $c->telefono }} @endif</small></td>
                    <td><span class="apr-badge e{{ $c->etapa_num }}">{{ $c->etapa }}</span></td>
                    <td>
                        @if ($c->derivada)<span class="apr-badge de">Equipo</span>@endif
                        @if ($c->abandonada)<span class="apr-badge ab">Se cortó</span>@endif
                    </td>
                    <td>{{ $c->turnos }}</td>
                    <td>@php $telC = preg_replace('/\D/', '', (string) $c->telefono); $apag = $pausados->has($telC); @endphp
                        <span class="apr-bot-est {{ $apag ? 'no' : 'si' }}">{{ $apag ? 'Apagado' : 'Activo' }}</span><br>
                        <form method="POST" action="{{ route('backoffice.bot-conversaciones.chat_bot') }}" style="display:inline">@csrf
                            <input type="hidden" name="telefono" value="{{ $telC }}"><input type="hidden" name="nombre" value="{{ $c->nombre }}">
                            <input type="hidden" name="accion" value="{{ $apag ? 'encender' : 'apagar' }}">
                            <button class="apr-bot-btn {{ $apag ? 'on' : 'off' }}">{{ $apag ? 'Encender' : 'Apagar' }}</button>
                        </form></td>
                    <td><span class="apr-link" data-conv="{{ $c->id }}" data-tel="{{ $c->telefono }}" data-canal="{{ $c->canal ?? 'whatsapp' }}">Ver</span></td>
                </tr>
            @empty
                <tr><td colspan="7" style="color:#64748b">No hay conversaciones en el período.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="grey-text" style="font-size:.75rem;margin-top:6px">Se guardan los últimos mensajes de cada conversación. No incluye los números de prueba.</p>
</div>

<div class="apr-modal" id="aprModal">
    <div class="apr-modal-card">
        <div class="apr-modal-head">
            <strong id="aprModalTit">Conversación</strong>
            <span>
                <a class="apr-btn" id="aprModalWa" target="_blank" href="#" style="color:#128c7e">WhatsApp</a>
                <button type="button" class="apr-btn" id="aprModalCerrar">Cerrar</button>
            </span>
        </div>
        <div class="apr-chat" id="aprChat"></div>
    </div>
</div>
@endsection

@section('foot')
<script>
(function () {
    var buscar = document.getElementById('convBuscar');
    buscar.addEventListener('input', function () {
        var q = buscar.value.trim().toLowerCase().replace(/\+/g, '');
        document.querySelectorAll('tr[data-buscar]').forEach(function (tr) {
            tr.style.display = !q || tr.dataset.buscar.indexOf(q) !== -1 ? '' : 'none';
        });
    });


    document.querySelectorAll('[data-filtro-canal]').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('[data-filtro-canal]').forEach(function (x) { x.classList.toggle('on', x === b); });
            var c = b.dataset.filtroCanal;
            document.querySelectorAll('tr[data-buscar]').forEach(function (tr) {
                tr.style.display = !c || (tr.dataset.canal || 'whatsapp') === c ? '' : 'none';
            });
        });
    });
    var modal = document.getElementById('aprModal'), chat = document.getElementById('aprChat');
    var urlBase = @json(route('backoffice.bot-configuracion.aprendizajes.conversacion', 0));
    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-conv]');
        if (!el) return;
        chat.innerHTML = '<p style="color:#64748b">Cargando…</p>';
        var esIg = el.dataset.canal === 'instagram', lk = document.getElementById('aprModalWa');
        lk.href = esIg ? 'https://www.instagram.com/direct/inbox/' : 'https://wa.me/' + (el.dataset.tel || '');
        lk.textContent = esIg ? 'Instagram' : 'WhatsApp'; lk.style.color = esIg ? '#9b1b52' : '#128c7e';
        modal.classList.add('open');
        fetch(urlBase.replace(/0$/, el.dataset.conv), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                document.getElementById('aprModalTit').textContent = (d.cliente || 'Conversación') + ' · #' + (d.id || '');
                chat.innerHTML = (d.mensajes || []).map(function (m) {
                    var et = { cliente: 'Cliente', bot: 'Bot', equipo: 'Equipo', sistema: '' }[m.de];
                    return '<div class="apr-msg ' + m.de + '">' + (et ? '<small style="display:block;font-weight:700;color:#64748b;">' + et + '</small>' : '') + esc(m.texto) + '</div>';
                }).join('') || '<p style="color:#64748b">Sin mensajes.</p>';
                chat.scrollTop = chat.scrollHeight;
            })
            .catch(function () { chat.innerHTML = '<p style="color:#b91c1c">No se pudo cargar la conversación.</p>'; });
    });
    document.getElementById('aprModalCerrar').onclick = function () { modal.classList.remove('open'); };
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.classList.remove('open'); });
})();
</script>
@endsection
