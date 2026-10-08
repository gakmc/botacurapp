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
            'menu_texto'                => ['Texto que acompaña el PDF', 'Te dejo nuestro menú 🍽️ Cuando lo revisen, me cuentan qué elige cada persona: 1 entrada, 1 fondo y 1 acompañamiento. Si su programa incluye desayuno u once, indíquenme también cuál prefiere cada uno.'],
            'menu_enviar_al_preguntar'  => ['Enviar el menú cuando un cliente lo pide (1 = sí, 0 = no)', '1'],
        ];
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
