<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * BotMenuService
 *
 * Menú en PDF que se envía por WhatsApp (tras confirmar el pago y, si está activado,
 * cuando el cliente lo pide). Se sube desde Configuración del bot → pestaña "Menú".
 * Si aún no se sube uno, usa el PDF antiguo public/docs/menu-otono-2026.pdf.
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class BotMenuService
{
    const ARCHIVO = 'docs/bot/menu.pdf';
    const ANTIGUO = 'docs/menu-otono-2026.pdf';

    public static function ajustesDefault(): array
    {
        return [
            'menu_titulo'               => ['Nombre del menú (ej. Menú Primavera 2026)', 'Menú Otoño 2026'],
            'menu_archivo_nombre'       => ['Nombre del archivo que ve el cliente', 'Menu-Botacura.pdf'],
            'menu_texto'                => ['Texto que acompaña el PDF', 'Te dejo nuestro menú 🍽️ Para cada persona necesitamos 1 entrada y 1 fondo (proteína + acompañamiento); el postre es del día. Si su programa incluye desayuno u once, cuéntenme cuál prefiere el grupo (es buffet).'],
            'menu_enviar_al_preguntar'  => ['Enviar el menú cuando un cliente lo pide (1 = sí, 0 = no)', '1'],
            'menu_fondos_unicos'        => ['Fondos que son plato único (no llevan acompañamiento), IDs', ''],
        ];
    }

    const PATRON_UNICO = '/pastel\s+de\s+papa|vegetarian|vegan|veggie/iu';

    /** Fondos activos del menú: [id, nombre, unico]. */
    public static function fondos(): array
    {
        try {
            $rows = DB::table('productos as p')
                ->join('tipos_productos as tp', 'tp.id', '=', 'p.id_tipo_producto')
                ->where('tp.nombre', 'fondo')
                ->where(function ($q) {
                    $q->where('p.estado', 'activo')->orWhereNull('p.estado');
                })
                ->orderBy('p.nombre')->get(['p.id', 'p.nombre']);
        } catch (\Throwable $e) {
            return [];
        }
        $unicos = self::fondosUnicos();
        return $rows->map(function ($r) use ($unicos) {
            return (object) ['id' => (int) $r->id, 'nombre' => $r->nombre, 'unico' => in_array((int) $r->id, $unicos, true)];
        })->all();
    }

    /** IDs de fondos que son plato único (Pastel de Papas y platos vegetarianos). */
    public static function fondosUnicos(): array
    {
        $txt = '';
        try {
            $txt = (string) DB::table('bot_secciones_texto')->where('clave', 'menu_fondos_unicos')->value('contenido');
        } catch (\Throwable $e) {
        }
        if (trim($txt) !== '') {
            return BotTarifasService::lista($txt);
        }
        // Sin configurar: se detectan por nombre.
        try {
            return DB::table('productos as p')->join('tipos_productos as tp', 'tp.id', '=', 'p.id_tipo_producto')
                ->where('tp.nombre', 'fondo')->get(['p.id', 'p.nombre'])
                ->filter(function ($r) { return preg_match(self::PATRON_UNICO, $r->nombre); })
                ->pluck('id')->map(function ($i) { return (int) $i; })->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function ajuste(string $clave): string
    {
        $def = self::ajustesDefault()[$clave][1] ?? '';
        try {
            $v = DB::table('bot_secciones_texto')->where('clave', $clave)->value('contenido');
        } catch (\Throwable $e) {
            $v = null;
        }
        return trim((string) $v) !== '' ? trim((string) $v) : $def;
    }

    public static function ruta(): ?string
    {
        foreach ([self::ARCHIVO, self::ANTIGUO] as $rel) {
            if (is_file(public_path($rel))) {
                return $rel;
            }
        }
        return null;
    }

    public static function url(): ?string
    {
        $rel = self::ruta();
        return $rel ? url($rel) . '?v=' . filemtime(public_path($rel)) : null;
    }

    public static function nombreArchivo(): string
    {
        $n = preg_replace('/[^A-Za-z0-9._-]+/', '-', self::ajuste('menu_archivo_nombre'));
        return preg_match('/\.pdf$/i', $n) ? $n : $n . '.pdf';
    }

    public static function titulo(): string
    {
        return self::ajuste('menu_titulo');
    }

    public static function texto(): string
    {
        return self::ajuste('menu_texto');
    }

    public static function enviarAlPreguntar(): bool
    {
        return self::ajuste('menu_enviar_al_preguntar') === '1';
    }

    public static function actualizado(): ?string
    {
        $rel = self::ruta();
        return $rel ? date('Y-m-d H:i', filemtime(public_path($rel))) : null;
    }
}
