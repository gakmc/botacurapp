<?php

namespace App\Http\Controllers;

use App\BotPasoFlujo;
use App\BotSeccionTexto;
use App\TipoMasaje;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

/**
 * Vista de edición del contenido del bot de WhatsApp: perfil de vendedor,
 * info del recinto (horarios/políticas/recomendaciones) y el guion paso a paso.
 * El catálogo de masajes se muestra en modo solo-lectura (se administra desde
 * Masajes > Valores; acá solo se ve un espejo de lo que el bot va a usar).
 *
 * Todo lo que se guarda acá es leído en vivo por BotPromptService cada vez que
 * el bot arma el prompt — no requiere deploy para tomar efecto.
 */
class BotConfiguracionController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:' . config('app.admin_role'));
    }

    public function index()
    {
        $perfil  = BotSeccionTexto::categoria('perfil')->get();
        $recinto = BotSeccionTexto::categoria('recinto')->get();
        $pasos   = BotPasoFlujo::orderBy('orden')->get();

        $masajes = TipoMasaje::activos()
            ->with(['categoria', 'precios' => function ($q) {
                $q->orderBy('duracion_minutos');
            }])
            ->whereHas('precios')
            ->get()
            ->groupBy(function ($t) {
                return $t->categoria->nombre ?? 'Otros';
            });

        return view('themes.backoffice.pages.bot-configuracion.index', compact('perfil', 'recinto', 'pasos', 'masajes'));
    }

    /**
     * Actualiza en bloque el contenido de las secciones de texto (perfil o recinto).
     * Espera $request->contenido = [clave => texto, ...]
     */
    public function updateSecciones(Request $request)
    {
        $request->validate([
            'contenido' => 'required|array',
            'contenido.*' => 'nullable|string',
        ]);

        foreach ($request->input('contenido') as $clave => $texto) {
            BotSeccionTexto::where('clave', $clave)->update([
                'contenido' => $texto,
                'updated_by' => auth()->id(),
            ]);
        }

        Alert::success('Éxito', 'Información actualizada. El bot ya está usando el nuevo texto.')->showConfirmButton();
        return redirect()->route('backoffice.bot-configuracion.index');
    }

    /**
     * Actualiza en bloque los pasos del guion del bot.
     * Espera $request->titulo[id], $request->instrucciones[id], $request->activo[id] (checkbox)
     */
    public function updatePasos(Request $request)
    {
        $request->validate([
            'titulo' => 'required|array',
            'instrucciones' => 'required|array',
        ]);

        $activos = $request->input('activo', []); // ids marcados como activos

        foreach ($request->input('titulo') as $id => $titulo) {
            BotPasoFlujo::where('id', $id)->update([
                'titulo' => $titulo,
                'instrucciones' => $request->input('instrucciones')[$id] ?? '',
                'activo' => in_array((string) $id, $activos),
                'updated_by' => auth()->id(),
            ]);
        }

        Alert::success('Éxito', 'Guion del bot actualizado.')->showConfirmButton();
        return redirect()->route('backoffice.bot-configuracion.index');
    }
}
