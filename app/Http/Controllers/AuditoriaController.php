<?php

namespace App\Http\Controllers;

use App\Auditoria;
use App\AuditoriaArchivo;
use App\AuditoriaAutorizado;
use App\Services\AuditoriaService;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditoriaController extends Controller
{
    const CATEGORIAS = [
        Auditoria::CATEGORIA_FINANCIERA  => 'Financiera',
        Auditoria::CATEGORIA_SEGURIDAD   => 'Seguridad',
        Auditoria::CATEGORIA_OPERACIONAL => 'Operacional',
    ];

    const ORIGENES = [
        Auditoria::ACTOR_USUARIO  => 'Usuario de la plataforma',
        Auditoria::ACTOR_CONSOLA  => 'Consola (manual)',
        Auditoria::ACTOR_SISTEMA  => 'Sistema (procesos y webhooks)',
        Auditoria::ACTOR_INVITADO => 'Visitante no identificado',
    ];

    /**
     * Registros que tienen historial propio: alias de URL => [clase, nombre visible].
     * El historial incluye las acciones sobre el registro y sobre sus registros
     * hijos (columna contexto), p. ej. pedidos y masajes de una reserva.
     */
    const HISTORIALES = [
        'reserva'        => [\App\Reserva::class, 'Reserva'],
        'usuario'        => [\App\User::class, 'Usuario'],
        'cliente'        => [\App\Cliente::class, 'Cliente'],
        'programa'       => [\App\Programa::class, 'Programa'],
        'producto'       => [\App\Producto::class, 'Producto'],
        'servicio'       => [\App\Servicio::class, 'Servicio'],
        'egreso'         => [\App\Egreso::class, 'Egreso'],
        'venta-directa'  => [\App\VentaDirecta::class, 'Venta directa'],
        'cotizacion'     => [\App\Cotizacion::class, 'Cotización'],
        'poroporo-venta' => [\App\PoroPoroVenta::class, 'Venta PoroPoro'],
        'proveedor'      => [\App\Proveedor::class, 'Proveedor'],
        'insumo'         => [\App\Insumo::class, 'Insumo'],
        'giftcard'       => [\App\GiftCard::class, 'Gift card'],
        'asignacion'     => [\App\Asignacion::class, 'Asignación de turno'],
        'asistencia'     => [\App\Asistencia::class, 'Asistencia'],
    ];

    // LISTADO

    public function index(Request $request)
    {
        $this->authorize('ver-auditoria');

        $request->validate([
            'user_id'    => 'nullable|integer',
            'categoria'  => 'nullable|in:' . implode(',', array_keys(self::CATEGORIAS)),
            'actor_tipo' => 'nullable|in:' . implode(',', array_keys(self::ORIGENES)),
            'evento'     => 'nullable|string|max:40',
            'desde'      => 'nullable|date',
            'hasta'      => 'nullable|date',
            'q'          => 'nullable|string|max:100',
        ]);

        $query = $this->consulta($request->boolean('incluir_archivo'), function ($q) use ($request) {
            foreach (['user_id', 'categoria', 'actor_tipo', 'evento'] as $campo) {
                if ($request->filled($campo)) {
                    $q->where($campo, $request->input($campo));
                }
            }
            if ($request->filled('desde')) {
                $q->where('created_at', '>=', Carbon::parse($request->desde)->startOfDay());
            }
            if ($request->filled('hasta')) {
                $q->where('created_at', '<=', Carbon::parse($request->hasta)->endOfDay());
            }
            if ($request->filled('q')) {
                $q->where('descripcion', 'like', '%' . $request->q . '%');
            }
        });

        $eventos = Auditoria::query()->distinct()->orderBy('evento')->pluck('evento')
            ->mapWithKeys(function ($evento) {
                return [$evento => Auditoria::etiquetaEvento($evento)];
            });

        return view('themes.backoffice.pages.auditoria.index', [
            'auditorias' => $query->orderByDesc('created_at')->orderByDesc('id')->paginate(50)->appends($request->query()),
            'usuarios'   => User::orderBy('name')->get(['id', 'name']),
            'eventos'    => $eventos,
            'categorias' => self::CATEGORIAS,
            'origenes'   => self::ORIGENES,
        ]);
    }

    // DETALLE

    public function show($id)
    {
        $this->authorize('ver-auditoria');

        // Puede estar en la tabla en caliente o ya archivado
        $auditoria = Auditoria::find($id) ?: AuditoriaArchivo::find($id);
        abort_unless($auditoria, 404);

        $relacionados = $auditoria->request_id
            ? $this->consulta(true, function ($q) use ($auditoria) {
                $q->where('request_id', $auditoria->request_id)->where('id', '!=', $auditoria->id);
            })->orderBy('id')->limit(100)->get()
            : collect();

        return view('themes.backoffice.pages.auditoria.show', [
            'auditoria'    => $auditoria,
            'relacionados' => $relacionados,
            'categorias'   => self::CATEGORIAS,
            'origenes'     => self::ORIGENES,
            'afectado'     => $this->enlaceHistorial($auditoria->auditable_type, $auditoria->auditable_id),
            'contexto'     => $this->enlaceHistorial($auditoria->contexto_type, $auditoria->contexto_id),
        ]);
    }

    // HISTORIAL DE UN REGISTRO

    public function historial($tipo, $id)
    {
        $this->authorize('ver-auditoria');

        abort_unless(isset(self::HISTORIALES[$tipo]), 404);
        list($clase, $nombre) = self::HISTORIALES[$tipo];

        // El historial siempre incluye lo archivado: es la trazabilidad completa del registro
        $auditorias = $this->consulta(true, function ($q) use ($clase, $id) {
            $q->where(function ($q) use ($clase, $id) {
                $q->where(function ($q) use ($clase, $id) {
                    $q->where('auditable_type', $clase)->where('auditable_id', $id);
                })->orWhere(function ($q) use ($clase, $id) {
                    $q->where('contexto_type', $clase)->where('contexto_id', $id);
                });
            });
        })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(50);

        return view('themes.backoffice.pages.auditoria.historial', [
            'auditorias' => $auditorias,
            'categorias' => self::CATEGORIAS,
            'titulo'     => $this->tituloRegistro($clase, $nombre, $id),
            'tipo'       => $tipo,
            'id'         => $id,
        ]);
    }

    /**
     * Consulta sobre `auditorias` y, opcionalmente, `auditorias_archivo` (UNION).
     * $filtros se aplica a ambas tablas. Cada fila trae `archivado` (0/1).
     */
    protected function consulta($incluirArchivo, callable $filtros)
    {
        $caliente = Auditoria::query()
            ->select(Auditoria::COLUMNAS_REGISTRO)
            ->addSelect(DB::raw('0 as archivado'));
        $filtros($caliente);

        if (! $incluirArchivo) {
            return $caliente;
        }

        $archivo = AuditoriaArchivo::query()
            ->select(Auditoria::COLUMNAS_REGISTRO)
            ->addSelect(DB::raw('1 as archivado'));
        $filtros($archivo);

        return $caliente->union($archivo->getQuery());
    }

    /** Alias de historial para una clase (o null si no tiene). */
    public static function aliasHistorial($clase)
    {
        foreach (self::HISTORIALES as $alias => $definicion) {
            if ($definicion[0] === $clase) {
                return $alias;
            }
        }

        return null;
    }

    /** ['texto' => ..., 'url' => ...|null] para mostrar un registro auditado. */
    protected function enlaceHistorial($clase, $id)
    {
        if (! $clase || ! $id) {
            return null;
        }

        $alias  = self::aliasHistorial($clase);
        $nombre = $alias ? self::HISTORIALES[$alias][1] : class_basename($clase);

        return [
            'texto' => $this->tituloRegistro($clase, $nombre, $id),
            'url'   => $alias ? route('backoffice.auditoria.historial', [$alias, $id]) : null,
        ];
    }

    protected function tituloRegistro($clase, $nombre, $id)
    {
        $modelo = class_exists($clase) ? $clase::find($id) : null;

        if (! $modelo) {
            return $nombre . ' #' . $id . ' (eliminado)';
        }
        if ($modelo instanceof \App\Reserva) {
            return ucfirst(app(AuditoriaService::class)->textoReserva($modelo));
        }

        $titulo = method_exists($modelo, 'tituloAuditoria') ? $modelo->tituloAuditoria() : '#' . $id;

        return $nombre . ' ' . ($titulo === '#' . $id ? $titulo : '"' . $titulo . '" (#' . $id . ')');
    }

    // GESTIÓN DE AUTORIZADOS (solo gestores)

    public function autorizados()
    {
        $this->authorize('gestionar-auditoria');

        return view('themes.backoffice.pages.auditoria.autorizados', [
            'autorizados' => AuditoriaAutorizado::with(['user', 'otorgante'])
                ->orderBy('nivel')
                ->orderBy('otorgado_at')
                ->get(),
            'candidatos'  => User::activos()
                ->whereDoesntHave('auditoriaAutorizacion')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * Otorga acceso de lector o gestor a un usuario sin acceso.
     * (Los gestores también se pueden crear por consola: auditoria:autorizar.)
     */
    public function autorizar(Request $request)
    {
        $this->authorize('gestionar-auditoria');

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'nivel'   => 'required|in:' . AuditoriaAutorizado::NIVEL_LECTOR . ',' . AuditoriaAutorizado::NIVEL_GESTOR,
        ], [
            'user_id.required' => 'Debes seleccionar un usuario.',
            'user_id.exists'   => 'El usuario seleccionado no existe.',
            'nivel.required'   => 'Debes seleccionar el nivel de acceso.',
            'nivel.in'         => 'El nivel de acceso no es válido.',
        ]);

        $user  = User::findOrFail($request->user_id);
        $nivel = $request->nivel;

        if (! $user->activo) {
            return back()->with('error', $user->name . ' está inactivo; no se le puede otorgar acceso.');
        }
        if ($user->auditoriaAutorizacion) {
            return back()->with('error', $user->name . ' ya tiene acceso a la auditoría.');
        }

        DB::transaction(function () use ($user, $nivel) {
            AuditoriaAutorizado::create([
                'user_id'      => $user->id,
                'nivel'        => $nivel,
                'otorgado_por' => auth()->id(),
                'otorgado_at'  => Carbon::now(),
            ]);

            app(AuditoriaService::class)->registrar([
                'categoria'   => Auditoria::CATEGORIA_SEGURIDAD,
                'evento'      => Auditoria::EVENTO_ASIGNO,
                'auditable'   => $user,
                'contexto'    => $user,
                'descripcion' => 'otorgó acceso de ' . $nivel . ' a la auditoría a ' . $user->name,
                'cambios'     => ['Acceso a auditoría' => ['Sin acceso', ucfirst($nivel)]],
            ]);
        });

        return redirect()->route('backoffice.auditoria.autorizados')
            ->with('success', $user->name . ' ahora es ' . $nivel . ' de la auditoría.');
    }

    /** Asciende un lector a gestor. */
    public function promover(AuditoriaAutorizado $autorizado)
    {
        $this->authorize('gestionar-auditoria');

        abort_unless($autorizado->exists, 404);

        $user = $autorizado->user;

        if ($autorizado->es_gestor()) {
            return back()->with('error', optional($user)->name . ' ya es gestor de la auditoría.');
        }
        if (! $user || ! $user->activo) {
            return back()->with('error', 'El usuario está inactivo; no se le puede dar acceso de gestor.');
        }

        DB::transaction(function () use ($autorizado, $user) {
            $autorizado->update([
                'nivel'        => AuditoriaAutorizado::NIVEL_GESTOR,
                'otorgado_por' => auth()->id(),
                'otorgado_at'  => Carbon::now(),
            ]);

            app(AuditoriaService::class)->registrar([
                'categoria'   => Auditoria::CATEGORIA_SEGURIDAD,
                'evento'      => Auditoria::EVENTO_ASIGNO,
                'auditable'   => $user,
                'contexto'    => $user,
                'descripcion' => 'cambió el acceso a la auditoría de ' . $user->name . ' de lector a gestor',
                'cambios'     => ['Acceso a auditoría' => ['Lector', 'Gestor']],
            ]);
        });

        return redirect()->route('backoffice.auditoria.autorizados')
            ->with('success', $user->name . ' ahora es gestor de la auditoría.');
    }

    /**
     * Revoca el acceso (lector o gestor). Nadie puede quitarse su propio acceso:
     * así siempre queda al menos un gestor (quien revoca).
     */
    public function revocar(AuditoriaAutorizado $autorizado)
    {
        $this->authorize('gestionar-auditoria');

        abort_unless($autorizado->exists, 404);

        if ((int) $autorizado->user_id === (int) auth()->id()) {
            return back()->with('error', 'No puedes quitarte tu propio acceso a la auditoría. Debe hacerlo otro gestor.');
        }

        $user  = $autorizado->user;
        $nivel = $autorizado->nivel;

        DB::transaction(function () use ($autorizado, $user, $nivel) {
            $autorizado->delete();

            app(AuditoriaService::class)->registrar([
                'categoria'   => Auditoria::CATEGORIA_SEGURIDAD,
                'evento'      => Auditoria::EVENTO_QUITO,
                'auditable'   => $user,
                'contexto'    => $user,
                'descripcion' => 'revocó el acceso de ' . $nivel . ' a la auditoría de ' . optional($user)->name,
                'cambios'     => ['Acceso a auditoría' => [ucfirst($nivel), 'Sin acceso']],
            ]);
        });

        return redirect()->route('backoffice.auditoria.autorizados')
            ->with('success', 'Se quitó el acceso a la auditoría a ' . optional($user)->name . '.');
    }
}
