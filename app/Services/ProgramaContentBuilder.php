<?php

namespace App\Services;

use App\Programa;
use App\Servicio;

class ProgramaContentBuilder
{
    // Servicios que no muestran duración aunque la tengan registrada
    private const SIN_DURACION = [
        'estacionamiento',
        'duchas',
        'casilleros',
        'estación de descanso',
        'estacion de descanso',
        'piscina',
    ];

    // Sub-ítems fijos del almuerzo
    private const SUBITEMS_ALMUERZO = [
        '   - Plato de fondo',
        '   - Ensalada',
        '   - Postre',
    ];

    public function build(Programa $programa): string
    {
        $programa->loadMissing('servicios');

        $html  = $this->buildServiciosSection($programa);
        $html .= $this->buildStaticSection($programa);

        return $html;
    }

    // ─────────────────────────────────────────────────────────────
    //  SECCIÓN DINÁMICA — lista de servicios
    // ─────────────────────────────────────────────────────────────

    private function buildServiciosSection(Programa $programa): string
    {
        $nombre = strtoupper($programa->nombre_programa);
        $html   = "<h1>{$nombre} contempla:</h1>\n";

        foreach ($programa->servicios as $servicio) {
            $html .= $this->buildServicioLine($servicio);
        }

        return $html;
    }

    private function buildServicioLine(Servicio $servicio): string
    {
        $nombre    = $servicio->nombre_servicio;
        $duracion  = $servicio->duracion; // accessor → minutos (int)
        $html      = '';

        $mostrarDuracion = $duracion > 0 && !$this->esSinDuracion($nombre);

        $linea = $mostrarDuracion
            ? "🌿{$nombre} ({$duracion} min)"
            : "🌿{$nombre}";

        $html .= "<p>{$linea}</p>\n";

        if ($this->esAlmuerzo($nombre)) {
            foreach (self::SUBITEMS_ALMUERZO as $subitem) {
                $html .= "<p>{$subitem}</p>\n";
            }
        }

        return $html;
    }

    // ─────────────────────────────────────────────────────────────
    //  SECCIÓN ESTÁTICA — políticas, condiciones, capacidad
    // ─────────────────────────────────────────────────────────────

    private function buildStaticSection(Programa $programa): string
    {
        $minPersonas  = $programa->min_personas ?? 1;
        $personaLabel = $minPersonas === 1 ? 'persona' : 'personas';

        $html  = "<p>";
        $html .= "<strong>✅Desde {$minPersonas} {$personaLabel}.</strong><br />";
        $html .= "<strong>⚠️Atendemos de Jueves a Domingo.</strong>";
        $html .= "</p>\n";

        $html .= "<p><strong>IMPORTANTE!</strong></p>\n";

        $html .= "<p><strong>👀Cupos diarios limitados. Consultar disponibilidad antes de comprar.</strong></p>\n";

        $html .= "<p>";
        $html .= "<strong>✅Al momento de realizar la compra, usted acepta políticas y condiciones del establecimiento ";
        $html .= "<a href=\"https://botacura.cl/privacy-policy/\">👀 Leer políticas</a></strong>";
        $html .= "</p>\n";

        $html .= "<p><strong>✅Finalizada la compra, nos contactaremos con ud. para coordinar el itinerario de su visita.</strong></p>\n";

        $html .= "<p><strong>✅Programa disponible solo con fecha fija.</strong></p>\n";

        if (!$this->esProgramaGiftCard($programa)) {
            $html .= "<p><strong>❌ No disponible para Gift Card</strong></p>\n";
        }else {
            $html .= "<p><strong>✅ Disponible para Gift Card</strong></p>\n";
        }

        $html .= "<p>&nbsp;</p>\n";

        return $html;
    }

    // ─────────────────────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────────────────────

    private function esAlmuerzo(string $nombre): bool
    {
        return strpos(strtolower($nombre), 'almuerzo') !== false;
    }

    private function esSinDuracion(string $nombre): bool
    {
        $nombreLower = strtolower($nombre);

        foreach (self::SIN_DURACION as $keyword) {
            if (strpos($nombreLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function esProgramaGiftCard(Programa $programa): bool
    {
        return (bool) $programa->permite_giftcard;
    }
}
