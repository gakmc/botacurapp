<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Conectar WhatsApp · BotacurApp</title>
<style>
:root{--bg:#f8fafc;--card:#fff;--tx:#0f172a;--mu:#64748b;--ok:#16a34a;--bad:#dc2626;--pri:#1877f2}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--tx);font:15px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
.w{max-width:720px;margin:40px auto;padding:0 16px}.c{background:var(--card);border-radius:16px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
h1{font-size:1.35rem;margin:0 0 6px}p{color:var(--mu);margin:6px 0 14px}ol{padding-left:20px;color:#334155}li{margin:6px 0}
.btn{display:inline-flex;align-items:center;gap:8px;background:var(--pri);color:#fff;border:0;border-radius:10px;padding:12px 18px;font-size:1rem;font-weight:600;cursor:pointer}
.btn:disabled{opacity:.5;cursor:not-allowed}.warn{background:#fffbeb;border:1px solid #fcd34d;color:#92400e;border-radius:10px;padding:10px 14px;margin:12px 0}
#res{margin-top:18px}.paso{display:flex;gap:10px;padding:8px 0;border-top:1px solid #f1f5f9}.paso b{width:20px;text-align:center}
.okc{color:var(--ok)}.badc{color:var(--bad)}code{background:#f1f5f9;padding:2px 6px;border-radius:6px}small{color:var(--mu)}
</style>
</head>
<body>
<div class="w"><div class="c">
  <h1>Conectar el WhatsApp de Bot-Acura</h1>
  <p>Conecta el número que hoy usan en la app WhatsApp Business a la API, sin perder la app ni los chats (coexistencia).</p>
  <ol>
    <li>Ten a mano el celular con la app <b>WhatsApp Business</b> del +56 9 7448 4112, actualizada.</li>
    <li>Presiona el botón, entra con tu Facebook y elige el portafolio <b>Botacura Cajón del Maipo</b> y la cuenta de WhatsApp <b>Botacura Cajón del Maipo</b>.</li>
    <li>Cuando pregunte por el número, elige <b>conectar tu app WhatsApp Business existente</b>, ingresa el número y escanea el código QR desde la app.</li>
    <li>Acepta compartir el historial de chats: el bot lo usa como contexto.</li>
  </ol>
  @if ($faltan)
    <div class="warn">Falta configurar en <code>.env</code>: {{ implode(', ', $faltan) }}.</div>
  @endif
  <button class="btn" id="go" {{ $faltan ? 'disabled' : '' }}>Conectar con Facebook</button>
  <div id="res"></div>
  <p><small>Después de conectar: el historial y los contactos se sincronizan solos (solo se puede hacer una vez, dentro de 24 horas).</small></p>
</div></div>
<script>
(function () {
  var datos = {}, res = document.getElementById('res');
  function linea(txt, ok, det) {
    var d = document.createElement('div'); d.className = 'paso';
    d.innerHTML = '<b class="' + (ok ? 'okc' : 'badc') + '">' + (ok ? '✓' : '✗') + '</b><div><span></span><br><small></small></div>';
    d.querySelector('span').textContent = txt; d.querySelector('small').textContent = det || '';
    res.appendChild(d);
  }
  window.addEventListener('message', function (e) {
    if (!/facebook\.com$/.test(new URL(e.origin).hostname)) return;
    try {
      var m = JSON.parse(e.data);
      if (m.type === 'WA_EMBEDDED_SIGNUP') {
        if (m.event === 'CANCEL') { linea('Proceso cancelado en el paso: ' + ((m.data && m.data.current_step) || '?'), false); return; }
        if (m.event === 'ERROR') { linea('Meta informó un error', false, (m.data && m.data.error_message) || ''); return; }
        datos = m.data || {}; datos.evento = m.event;
        linea('Meta informó: ' + m.event, /^FINISH/.test(m.event), 'cuenta ' + (datos.waba_id || '?') + ' · número ' + (datos.phone_number_id || '?'));
      }
    } catch (x) {}
  });
  window.fbAsyncInit = function () {
    FB.init({ appId: @json($appId), autoLogAppEvents: true, xfbml: false, version: @json($ver) });
  };
  document.getElementById('go').addEventListener('click', function () {
    var b = this; b.disabled = true; res.innerHTML = '';
    FB.login(function (r) {
      if (!r.authResponse || !r.authResponse.code) { linea('No se completó el inicio de sesión', false, JSON.stringify(r || {})); b.disabled = false; return; }
      if (!datos.evento) linea('Meta no informó el resultado del registro (puede que no se haya terminado)', false);
      var code = r.authResponse.code;
      setTimeout(function () { // dar tiempo a que llegue el evento con waba_id y phone_number_id
        fetch(@json(route('whatsapp.conectar.finalizar')), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
          body: JSON.stringify({ pagina: location.origin + location.pathname, code: code, waba_id: datos.waba_id || null, phone_number_id: datos.phone_number_id || null, evento: datos.evento || null })
        }).then(function (x) { return x.json(); }).then(function (j) {
          (j.pasos || []).forEach(function (p) { linea(p[0], p[1], p[2]); });
          if (j.phone_number_id) linea('Identificador del número: ' + j.phone_number_id + ' — guárdalo en META_PHONE_NUMBER_ID', true);
          b.disabled = false;
        }).catch(function (err) { linea('Error al terminar la conexión', false, String(err)); b.disabled = false; });
      }, 1200);
    }, {
      config_id: @json($configId),
      response_type: 'code',
      override_default_response_type: true,
      extras: { setup: {}, featureType: 'whatsapp_business_app_onboarding', sessionInfoVersion: '3' }
    });
  });
})();
</script>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/es_LA/sdk.js"></script>
</body>
</html>
