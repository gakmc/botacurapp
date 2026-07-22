#!/bin/bash
# fix_automaciones_tinajas.sh
# Reemplaza las 4 automaciones de tinaja buggeadas por 2 corregidas
# Ejecutar via: ssh botacura@192.168.100.73 'bash -s' < fix_automaciones_tinajas.sh

set -e
CONF="/home/botacura/homeassistant/config"
FILE="$CONF/automations.yaml"

echo "=== Backup ==="
cp "$FILE" "${FILE}.bak_$(date +%Y%m%d_%H%M%S)"
echo "Backup creado."

echo "=== Eliminando 4 automaciones antiguas ==="
python3 - << 'PYEOF'
import re

with open('/home/botacura/homeassistant/config/automations.yaml', 'r') as f:
    content = f.read()

# Separar en bloques (cada bloque empieza con "- id:")
blocks = re.split(r'\n(?=- id:)', content)

remove_ids = [
    'tinajas_actualizar_encendido_t1',
    'tinajas_actualizar_encendido_t2',
    'tinajas_encender_calefaccion_t1',
    'tinajas_encender_calefaccion_t2',
    'auto_calefaccion_tinaja_1',
    'auto_calefaccion_tinaja_2',
]

before = len(blocks)
filtered = [b for b in blocks if not any(f'id: {rid}' in b for rid in remove_ids)]
after = len(filtered)

with open('/home/botacura/homeassistant/config/automations.yaml', 'w') as f:
    content = '\n'.join(filtered)
    # Asegurar que termina sin linea en blanco extra
    f.write(content.rstrip('\n') + '\n')

print(f"Eliminadas: {before - after} automaciones. Restantes: {after}")
PYEOF

echo "=== Agregando 2 automaciones corregidas ==="
cat >> "$FILE" << 'YAMLEOF'

- id: tinajas_encender_calefaccion_t1
  alias: "Tinajas: Encender calefaccion T1 con anticipacion"
  description: "Enciende calefaccion T1 segun proxima reserva y tiempo estimado. Usa datetime completo (funciona dias futuros)."
  trigger:
    - platform: time_pattern
      minutes: "/1"
  condition:
    - condition: state
      entity_id: input_boolean.tinajas_control_activo
      state: "on"
    - condition: state
      entity_id: switch.sonoff_100136bad0_2
      state: "off"
    - condition: template
      value_template: >
        {% set t1 = state_attr('sensor.reservas_tinajas', 'tinaja_1') %}
        {% if t1 and t1.datetime_reserva %}
          {% set reserva = strptime(t1.datetime_reserva, '%Y-%m-%d %H:%M:%S') | as_datetime %}
          {% set minutos = states('sensor.minutos_para_calentar_t1') | int(0) + 5 %}
          {% set inicio = reserva - timedelta(minutes=minutos) %}
          {{ now() >= inicio and now() < reserva }}
        {% else %}
          false
        {% endif %}
  action:
    - service: switch.turn_on
      target:
        entity_id: switch.sonoff_100136bad0_2
    - service: persistent_notification.create
      data:
        title: "🔥 Tinaja 1 — Calefacción automática"
        message: >
          {% set t1 = state_attr('sensor.reservas_tinajas', 'tinaja_1') %}
          Encendida para {{ t1.cliente }}. Reserva {{ t1.horario }}.
          Tiempo estimado: {{ states('sensor.minutos_para_calentar_t1') }} min.
        notification_id: auto_calefaccion_t1
  mode: single

- id: tinajas_encender_calefaccion_t2
  alias: "Tinajas: Encender calefaccion T2 con anticipacion"
  description: "Enciende calefaccion T2 segun proxima reserva y tiempo estimado. Usa datetime completo (funciona dias futuros)."
  trigger:
    - platform: time_pattern
      minutes: "/1"
  condition:
    - condition: state
      entity_id: input_boolean.tinajas_control_activo
      state: "on"
    - condition: state
      entity_id: switch.sonoff_100136b567_2
      state: "off"
    - condition: template
      value_template: >
        {% set t2 = state_attr('sensor.reservas_tinajas', 'tinaja_2') %}
        {% if t2 and t2.datetime_reserva %}
          {% set reserva = strptime(t2.datetime_reserva, '%Y-%m-%d %H:%M:%S') | as_datetime %}
          {% set minutos = states('sensor.minutos_para_calentar_t2') | int(0) + 5 %}
          {% set inicio = reserva - timedelta(minutes=minutos) %}
          {{ now() >= inicio and now() < reserva }}
        {% else %}
          false
        {% endif %}
  action:
    - service: switch.turn_on
      target:
        entity_id: switch.sonoff_100136b567_2
    - service: persistent_notification.create
      data:
        title: "🔥 Tinaja 2 — Calefacción automática"
        message: >
          {% set t2 = state_attr('sensor.reservas_tinajas', 'tinaja_2') %}
          Encendida para {{ t2.cliente }}. Reserva {{ t2.horario }}.
          Tiempo estimado: {{ states('sensor.minutos_para_calentar_t2') }} min.
        notification_id: auto_calefaccion_t2
  mode: single
YAMLEOF

echo "=== Verificando config ==="
cd "$CONF"
python3 -c "import yaml; yaml.safe_load(open('automations.yaml')); print('YAML valido ✓')"

echo "=== Recargando automaciones en HA ==="
curl -s -o /dev/null -w "HTTP %{http_code}" \
  -X POST http://localhost:8123/api/services/automation/reload \
  -H "Authorization: Bearer $(grep botacura_iot_token secrets.yaml | awk '{print $2}')" \
  -H "Content-Type: application/json"

echo ""
echo "=== LISTO ==="
