<?php

namespace App\Http\Controllers;

use App\Services\BotAprendizajeService;
use App\Services\BotInteresFechasService;
use Illuminate\Http\Request;

/**
 * Conversaciones del bot (13/10/2026): solo el listado "Conversaciones recientes" de
 * Configuracion del bot > Aprendizajes, con el mismo calculo (BotAprendizajeService),
 * sin metricas ni sugerencias. "Ver" abre el chat con la misma ruta que usa Aprendizajes.
 *
 * 14/10/2026: "Dias pedidos sin habilitar" (BotInteresFechasService): conteo por fecha,
 * interesados y boton para avisarles cuando el dia se habilite.
 */
class BotConversacionesController extends Controller
{
    private function soloAdmin()
    {
        $user = auth()->user();
        abort_unless($user && $user->has_role(config('app.admin_role')), 403);
        return $user;
    }

    public function index(Request $request)
    {
        $this->soloAdmin();

        $dias = (int) $request->input('dias', 14);
        if (!in_array($dias, [1, 7, 14, 30, 90], true)) {
            $dias = 14;
        }
        $conversaciones = BotAprendizajeService::conversacionesRecientes($dias, 300);

        try {
            $diasPedidos = BotInteresFechasService::resumen();
        } catch (\Throwable $e) {
            \Log::warning('[BotInteres] resumen: ' . $e->getMessage());
            $diasPedidos = [];
        }
        $umbral = BotInteresFechasService::umbral();

        return view('themes.backoffice.pages.bot-conversaciones.index', compact('conversaciones', 'dias', 'diasPedidos', 'umbral'));
    }

    public function avisarInteres(Request $request)
    {
        $user = $this->soloAdmin();
        $res = BotInteresFechasService::avisarFecha((string) $request->input('fecha'), $user->id ?? null);
        return back()->with('interes_msg', $res['mensaje'])->with('interes_ok', $res['ok']);
    }

    public function estadoInteres(Request $request, $id)
    {
        $user = $this->soloAdmin();
        $ok = BotInteresFechasService::cambiarEstado((int) $id, (string) $request->input('estado'), $user->id ?? null);
        return back()->with('interes_msg', $ok ? 'Actualizado.' : 'No se pudo actualizar.')->with('interes_ok', $ok);
    }
}
