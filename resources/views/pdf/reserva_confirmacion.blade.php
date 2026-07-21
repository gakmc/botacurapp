<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; background: #fff; }

  .page { padding: 30px 40px; }

  /* ── Header ── */
  .header { border-bottom: 3px solid #2d7a4f; padding-bottom: 16px; margin-bottom: 20px; }
  .header h1 { font-size: 22px; color: #2d7a4f; font-weight: bold; letter-spacing: 0.5px; }
  .header .subtitle { font-size: 11px; color: #666; margin-top: 4px; }
  .header .logo-row { display: flex; justify-content: space-between; align-items: flex-end; }
  .header .tag { background: #2d7a4f; color: #fff; font-size: 10px; padding: 3px 8px; border-radius: 3px; }

  /* ── Confirmation banner ── */
  .banner { background: #e8f5ee; border: 1.5px solid #2d7a4f; border-radius: 6px; padding: 12px 16px; margin-bottom: 18px; text-align: center; }
  .banner h2 { font-size: 16px; color: #2d7a4f; font-weight: bold; }
  .banner p { font-size: 11px; color: #555; margin-top: 3px; }

  /* ── Sections ── */
  .section { margin-bottom: 18px; }
  .section-title { font-size: 11px; font-weight: bold; color: #2d7a4f; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #cde9d8; padding-bottom: 4px; margin-bottom: 10px; }

  /* ── Info table ── */
  .info-table { width: 100%; border-collapse: collapse; }
  .info-table td { padding: 5px 8px; vertical-align: top; }
  .info-table td.label { font-weight: bold; color: #555; width: 38%; font-size: 11px; }
  .info-table td.value { color: #222; font-size: 11px; }
  .info-table tr:nth-child(odd) td { background: #f8faf9; }

  /* ── Payment boxes ── */
  .pay-box { border: 1.5px solid #ddd; border-radius: 6px; padding: 12px 14px; margin-bottom: 12px; }
  .pay-box.highlight { border-color: #2d7a4f; background: #f3faf6; }
  .pay-box h3 { font-size: 12px; font-weight: bold; color: #2d7a4f; margin-bottom: 6px; }
  .pay-box p { font-size: 11px; color: #444; line-height: 1.6; }
  .pay-box .ref { background: #fff3cd; border: 1px solid #f0c040; border-radius: 4px; padding: 5px 10px; font-size: 13px; font-weight: bold; color: #7a5400; margin: 6px 0; display: block; }
  .pay-box .amount { font-size: 14px; font-weight: bold; color: #2d7a4f; }
  .pay-box .link { font-size: 11px; color: #1a73e8; word-break: break-all; }

  /* ── Warning ── */
  .warning { background: #fff8e1; border-left: 4px solid #f9a825; padding: 8px 12px; font-size: 10.5px; color: #5a4200; border-radius: 0 4px 4px 0; margin-top: 8px; line-height: 1.5; }

  /* ── Footer ── */
  .footer { margin-top: 24px; border-top: 1px solid #ddd; padding-top: 12px; text-align: center; font-size: 9.5px; color: #888; line-height: 1.7; }
</style>
</head>
<body>
<div class="page">

  {{-- ─── Header ─── --}}
  <div class="header">
    <div class="logo-row">
      <div>
        <h1>🌿 Botacura Spa</h1>
        <div class="subtitle">Cajón del Maipo · hola@botacura.cl · +56 9 7448 4112</div>
      </div>
      <div class="tag">CONFIRMACIÓN DE RESERVA</div>
    </div>
  </div>

  {{-- ─── Banner ─── --}}
  <div class="banner">
    <h2>¡Tu reserva está confirmada! 🎉</h2>
    <p>Hemos recibido tu pre-reserva. Quedará confirmada una vez que realices el abono.</p>
  </div>

  {{-- ─── Datos de reserva ─── --}}
  <div class="section">
    <div class="section-title">Detalle de la reserva</div>
    <table class="info-table">
      <tr>
        <td class="label">N° de reserva</td>
        <td class="value">#{{ $reserva->id }}</td>
      </tr>
      <tr>
        <td class="label">Cliente</td>
        <td class="value">{{ $cliente->nombre_cliente }}</td>
      </tr>
      @if($cliente->whatsapp_cliente)
      <tr>
        <td class="label">Teléfono</td>
        <td class="value">+{{ $cliente->whatsapp_cliente }}</td>
      </tr>
      @endif
      @if($cliente->correo && strpos($cliente->correo, '@temporal.botacura.cl') === false)
      <tr>
        <td class="label">Email</td>
        <td class="value">{{ $cliente->correo }}</td>
      </tr>
      @endif
      <tr>
        <td class="label">Programa</td>
        <td class="value">{{ $programa->nombre_programa }}</td>
      </tr>
      <tr>
        <td class="label">Fecha de visita</td>
        <td class="value">{{ \Carbon\Carbon::parse($reserva->fecha_visita)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}</td>
      </tr>
      <tr>
        <td class="label">Personas</td>
        <td class="value">{{ $reserva->cantidad_personas }} {{ $reserva->cantidad_personas == 1 ? 'persona' : 'personas' }}</td>
      </tr>
      @if($tipoServicio)
      <tr>
        <td class="label">Servicio extra</td>
        <td class="value">{{ ucfirst($tipoServicio) }}</td>
      </tr>
      @endif
    </table>
  </div>

  {{-- ─── Pago ─── --}}
  <div class="section">
    <div class="section-title">Opciones de pago</div>

    {{-- Webpay --}}
    <div class="pay-box">
      <h3>💳 Opción 1 — Pago completo con tarjeta (100% ahora)</h3>
      <p>
        Monto total: <span class="amount">{{ $totalFormato }}</span><br>
        Ingresa al siguiente enlace para pagar con débito, crédito o prepago de forma segura con Transbank:
      </p>
      <p class="link">{{ $enlacePago }}</p>
    </div>

    {{-- Transferencia --}}
    <div class="pay-box highlight">
      <h3>🏦 Opción 2 — Transferencia bancaria (50% de abono)</h3>
      <p>
        Abono: <span class="amount">{{ $abono50Formato }}</span>
        &nbsp;·&nbsp; Saldo el día de visita: <span class="amount">{{ $abono50Formato }}</span>
      </p>
      <p style="margin-top:8px;"><strong>Datos bancarios:</strong></p>
      <p>
        Banco: {{ env('BANCO_NOMBRE', 'Botacura SpA') }}<br>
        N° cuenta: {{ env('BANCO_NUMERO_CUENTA', '—') }}<br>
        RUT: {{ env('BANCO_RUT', '—') }}<br>
        Email: {{ env('BANCO_EMAIL', 'hola@botacura.cl') }}
      </p>
      <p style="margin-top:8px;"><strong>Código de referencia (OBLIGATORIO):</strong></p>
      <span class="ref">{{ $referencia }}</span>
      <div class="warning">
        ⚠️ Debes escribir este código en el campo <strong>Mensaje / Motivo</strong> de la transferencia.
        Sin este código no podremos asociar tu pago a la reserva automáticamente.
      </div>
    </div>
  </div>

  {{-- ─── Servicios incluidos ─── --}}
  @if(count($servicios) > 0)
  <div class="section">
    <div class="section-title">Servicios incluidos en tu programa</div>
    <table class="info-table">
      @foreach($servicios as $servicio)
      <tr>
        <td class="value" colspan="2">✔ {{ $servicio }}</td>
      </tr>
      @endforeach
    </table>
  </div>
  @endif

  {{-- ─── Footer ─── --}}
  <div class="footer">
    Botacura Spa · Cajón del Maipo, Región Metropolitana, Chile<br>
    hola@botacura.cl · +56 9 7448 4112 · www.botacura.cl<br>
    Este documento fue generado automáticamente el {{ now()->format('d/m/Y H:i') }} hrs.
  </div>

</div>
</body>
</html>
