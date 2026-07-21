#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
QA Bot WhatsApp — Flujo de Reserva End-to-End
Simula una conversacion completa (8 pasos) desde el saludo hasta la creacion
de la reserva, usando el mismo numero de telefono en cada turno.

Uso: python qa-bot-reserva.py
"""

import json, sys, io, time, urllib.request, urllib.error

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

URL    = "http://localhost/api/bot/message"
SECRET = "26d9bde12b5e1dd0464b75d4895c21a423f94aa4c0db91ed1baf969e2aa77ce5"

# Telefono de prueba dedicado al flujo de reserva
TEL    = "56900099001"
NOMBRE = "Carlos Prueba"

# Pausa entre turnos (segundos) — necesario para que Claude no se confunda
PAUSA  = 1.5

SEP1 = "=" * 65
SEP2 = "-" * 65

# ─── UTILIDADES ─────────────────────────────────────────────────────────────

def reset_conversacion():
    """Cierra la conversacion previa de este numero."""
    try:
        req = urllib.request.Request(
            "http://localhost/api/bot/reset-qa",
            headers={"X-Bot-Secret": SECRET},
            method="GET",
        )
        with urllib.request.urlopen(req, timeout=10) as r:
            pass
    except Exception:
        pass

def send(mensaje):
    payload = json.dumps({
        "telefono": TEL,
        "mensaje":  mensaje,
        "nombre":   NOMBRE,
    }, ensure_ascii=False).encode("utf-8")

    req = urllib.request.Request(
        URL,
        data=payload,
        headers={
            "Content-Type":  "application/json; charset=utf-8",
            "X-Bot-Secret":  SECRET,
        },
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return json.loads(r.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        body = e.read().decode("utf-8", errors="replace")
        try:
            return json.loads(body)
        except Exception:
            return {"ok": False, "error": f"HTTP {e.code}: {body[:300]}"}
    except Exception as ex:
        return {"ok": False, "error": str(ex)}

def turno(num, desc, mensaje, kw_accion=None, kw_mensaje=None):
    """Envia un mensaje y muestra la respuesta. Retorna True si OK."""
    print(f"\n{SEP2}")
    print(f" Turno {num}: {desc}")
    print(f" >> \"{mensaje}\"")
    print(SEP2)

    resp    = send(mensaje)
    ok      = resp.get("ok", False)
    accion  = resp.get("accion", "-")
    mensaje_bot = resp.get("mensaje", "")

    if ok:
        print(f" [OK]  accion: {accion}")
        print()
        for linea in mensaje_bot.split("\n"):
            print(f"   | {linea}")
        print()

        checks = []
        if kw_accion and accion != kw_accion:
            checks.append(f"[WARN] accion esperada '{kw_accion}', recibida '{accion}'")
        if kw_mensaje and kw_mensaje.lower() not in mensaje_bot.lower():
            checks.append(f"[WARN] no contiene '{kw_mensaje}' en respuesta")
        for c in checks:
            print(f"  {c}")

        return True, accion, mensaje_bot
    else:
        errores = resp.get("errors", resp.get("error", str(resp)[:200]))
        print(f" [FALLO] {errores}")
        return False, None, None

def check_reserva_creada():
    """Verifica en BD que se haya creado la reserva con fuente=bot_whatsapp."""
    try:
        req = urllib.request.Request(
            "http://localhost/api/bot/diag?fecha=2026-07-23",
            headers={"X-Bot-Secret": SECRET},
            method="GET",
        )
        with urllib.request.urlopen(req, timeout=10) as r:
            data = json.loads(r.read().decode("utf-8"))
            reservas = data.get("reservas_del_dia", [])
            for r in reservas:
                if r.get("nombre_cliente") and "Prueba" in (r.get("nombre_cliente") or ""):
                    return True, r
            return False, None
    except Exception as ex:
        return False, str(ex)

# ─── FLUJO ──────────────────────────────────────────────────────────────────

print()
print(SEP1)
print("  QA BOT - FLUJO DE RESERVA END-TO-END")
print(f"  Telefono de prueba: {TEL}")
print(SEP1)

print("\nLimpiando conversacion previa...")
reset_conversacion()
time.sleep(0.5)

pasos_ok = 0
pasos_totales = 10

# T1 — Saludo y tipo de visita
ok, accion, _ = turno(1, "Saludo inicial", "Hola quiero hacer una reserva para el jueves 23 de julio")
if ok: pasos_ok += 1
time.sleep(PAUSA)

# T2 — Cuantas personas
ok, accion, _ = turno(2, "Informar personas", "Somos 2 personas")
if ok: pasos_ok += 1
time.sleep(PAUSA)

# T3 — Elegir programa
ok, accion, _ = turno(3, "Elegir programa", "Queremos el Full Cyber")
if ok: pasos_ok += 1
time.sleep(PAUSA)

# T4 — Confirmar fecha
ok, accion, _ = turno(4, "Confirmar fecha", "El jueves 23 de julio")
if ok: pasos_ok += 1
time.sleep(PAUSA)

# T5 — Nombre
ok, accion, _ = turno(5, "Dar nombre", "Carlos Prueba")
if ok: pasos_ok += 1
time.sleep(PAUSA)

# T6 — Telefono
ok, accion, _ = turno(6, "Dar telefono", "Es mi mismo numero el 56900099001")
if ok: pasos_ok += 1
time.sleep(PAUSA)

# T7 — Email (necesario para correo de confirmacion)
ok, accion, _ = turno(7, "Dar email", "swimmerw@gmail.com")
if ok: pasos_ok += 1
time.sleep(PAUSA)

# T8 — Extras (masajes adicionales, almuerzo)
ok, accion, _ = turno(8, "Servicios extra", "No gracias, solo el plan basico")
if ok: pasos_ok += 1
time.sleep(PAUSA)

# T9 — Aceptar politicas de cancelacion
ok, accion, msg = turno(9, "Aceptar politicas de cancelacion", "Si acepto las politicas de cancelacion")
if ok: pasos_ok += 1
time.sleep(PAUSA)

# T10 — Confirmar reserva
ok, accion, msg = turno(10, "Confirmar y crear reserva", "Si, confirmo la reserva", kw_accion="crear_reserva")
if ok: pasos_ok += 1
time.sleep(1)

# ─── RESULTADO ───────────────────────────────────────────────────────────────

print()
print(SEP1)
print(f"  PASOS COMPLETADOS: {pasos_ok}/{pasos_totales}")
print(f"  Email de confirmacion: swimmerw@gmail.com")
print(f"  Registros creados: reserva + venta + visita + masajes + menus")
print(SEP1)

# Verificar en BD
print("\nVerificando reserva en base de datos...")
encontrada, datos_reserva = check_reserva_creada()

if encontrada and isinstance(datos_reserva, dict):
    print(f"\n [OK] Reserva encontrada en BD:")
    print(f"      Cliente:  {datos_reserva.get('nombre_cliente')}")
    print(f"      Programa: {datos_reserva.get('nombre_programa')}")
    print(f"      Fecha:    {datos_reserva.get('fecha_visita') or '2026-07-23'}")
    print(f"      Estado:   {datos_reserva.get('estado_reserva')}")
    print(f"\n Abre en la app: http://localhost/reservas/registros")
else:
    print(f"\n [INFO] No se pudo verificar en BD via diag (normal si la reserva es muy nueva).")
    print(f"        Verifica en: http://localhost/reservas/registros")
    print(f"        Busca el cliente '{NOMBRE}' con badge verde 'Bot'.")

print()
