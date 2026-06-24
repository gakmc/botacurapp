# Flujos de Integración — botacurapp

## Resumen del problema y soluciones

| Tabla | Problema |
|---|---|
| `proveedores` | Sin categoría → al seleccionar proveedor aparecen todos mezclados |
| `egresos` | Solo 8 campos: sin descripción, sin fecha explícita, sin número de documento |
| `categorias_compras` | Sin relación con proveedores |
| `pagos_egresos` | Sin método de pago, sin comprobante |

---

## Migración 001 — Categorías por proveedor

**Qué cambia:**
- `categoria_compra_id` en `proveedores` → al elegir categoría "Gas", solo aparecen proveedores de gas
- Campos adicionales: `direccion`, `contacto_nombre`, `notas`, `activo`
- Tabla pivot `proveedor_categorias` para proveedores con múltiples categorías

**Filtro en la app:**
```sql
-- Opción A: categoría principal
WHERE p.categoria_compra_id = :categoriaId

-- Opción B: pivot (proveedores con múltiples categorías)
JOIN proveedor_categorias pc ON pc.proveedor_id = p.id
WHERE pc.categoria_compra_id = :categoriaId
```

---

## Migración 002 — Mejoras a egresos

**Campos agregados a `egresos`:**
- `descripcion` — texto libre
- `fecha_egreso` — fecha real del gasto
- `numero_documento` — N° de factura o boleta
- `metodo_pago` — efectivo / transferencia / tarjeta
- `estado` — pendiente / pagado / anulado
- `fuente` — manual / home_assistant / ai_scan
- `observaciones` — notas internas

---

## Migración 003 — Tablas nuevas

### `egreso_items` — Líneas de factura
Cada egreso puede tener múltiples ítems. Ejemplo:
```
Factura Abastible:
  - Gas 45kg     x1  $18.500
  - Válvula reg  x1   $2.000
  Total: $20.500
```

### `egreso_documentos` — Archivo + datos IA
JSON extraído por la IA:
```json
{
  "proveedor": "Abastible S.A.",
  "rut_proveedor": "90.814.000-1",
  "fecha": "2026-06-20",
  "numero": "003421",
  "total": 20500,
  "neto": 17227,
  "iva": 3273,
  "items": [
    {"descripcion": "Gas 45kg", "cantidad": 1, "precio": 18500},
    {"descripcion": "Válvula reguladora", "cantidad": 1, "precio": 2000}
  ]
}
```

### `ha_webhooks` + `ha_webhook_logs` — Home Assistant
Token secreto por dispositivo. HA llama a un endpoint y el sistema crea el egreso automáticamente.

---

## Flujo 1: Registro vía Home Assistant

```
[Sensor HA detecta recarga gas]
        ↓
[Automatización HA dispara HTTP POST]
POST https://botacura.cl/api/egreso/webhook
Headers: X-Webhook-Token: {token_secreto}
Body: { "total": 18500, "fecha": "2026-06-20", "descripcion": "Recarga gas cocina" }
        ↓
[PHP: valida token → busca ha_webhook → crea egreso con fuente='home_assistant']
        ↓
[Log en ha_webhook_logs]
```

**Configuración HA (configuration.yaml):**
```yaml
rest_command:
  registrar_egreso_gas:
    url: "https://botacura.cl/api/egreso/webhook"
    method: POST
    headers:
      X-Webhook-Token: "tu_token_secreto_aqui"
      Content-Type: "application/json"
    payload: '{"total": {{ states("sensor.precio_gas") }}, "descripcion": "Recarga automática gas"}'
```

---

## Flujo 2: Registro vía PDF/Foto con IA

```
[Usuario sube foto o PDF de boleta/factura]
        ↓
[PHP guarda archivo → crea egreso_documentos (procesado=0)]
        ↓
[Cola: llama a API Claude/GPT-4 Vision con la imagen]
        ↓
[IA extrae: proveedor, RUT, fecha, número, ítems, total, IVA]
        ↓
[PHP actualiza egreso_documentos (procesado=1, datos_extraidos=JSON)]
        ↓
[Formulario pre-llenado → usuario confirma → se crean egresos + egreso_items]
```

**Prompt para la IA:**
```
Analiza esta factura o boleta chilena y extrae en JSON:
- proveedor (nombre empresa)
- rut_proveedor
- fecha (formato YYYY-MM-DD)
- numero_documento
- neto (monto sin IVA)
- iva
- total
- items: array de {descripcion, cantidad, unidad, precio_unitario, subtotal}
Si no encuentras algún campo, usa null. Responde SOLO el JSON.
```

---

## Cómo aplicar en producción

```bash
# 1. Ya estás en la rama correcta:
#    feature/proveedores-egresos-mejoras

# 2. Los SQL están en database/sql-migrations/
#    Ejecutar EN ORDEN en una BD de desarrollo primero

# 3. Commit y push
git add database/sql-migrations/
git commit -m "feat: mejoras proveedores, egresos e integracion HA+IA"
git push origin feature/proveedores-egresos-mejoras

# 4. Cuando esté probado → Pull Request a main
```
