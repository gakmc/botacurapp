<?php

namespace App\Http\Controllers;

use App\Role;
use App\Services\TicketService;
use App\Ticket;
use App\TicketImagen;
use App\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RealRashid\SweetAlert\Facades\Alert;

/**
 * Tickets entre departamentos. La autorización vive en TicketPolicy y las
 * reglas de estado en App\Ticket.
 */
class TicketController extends Controller
{
    const FILTROS = ['todos' => 'Todos', 'urgentes' => 'Urgentes', 'sin_tomar' => 'Sin tomar', 'mios' => 'Míos'];

    /**
     * Bandeja principal: tickets activos. Recibidos y Enviados se arman juntos
     * (en escritorio van lado a lado; en celular, en pestañas).
     * El administrador, salvo que marque "Solo los de mi rol", ve todos los departamentos.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);
        $user = auth()->user();

        $supervisa = $user->supervisaTickets();
        $supervision = $this->enSupervision($request, $user);

        $bandeja = $request->input('bandeja') === 'enviados' ? 'enviados' : 'recibidos';
        $filtros = $supervision ? Arr::except(self::FILTROS, 'mios') : self::FILTROS;
        $filtro = array_key_exists($request->input('filtro'), $filtros) ? $request->input('filtro') : 'todos';

        $datos = [
            'supervisa'   => $supervisa,
            'supervision' => $supervision,
            'bandeja'     => $bandeja,
            'filtro'      => $filtro,
            'filtros'     => $filtros,
        ];

        if ($supervision) {
            $departamento = (int) $request->input('departamento') ?: null;
            $todos = $this->consultaBandeja()
                ->when($departamento, function ($q) use ($departamento) {
                    $q->where(function ($q) use ($departamento) {
                        $q->where('rol_origen_id', $departamento)->orWhere('rol_destino_id', $departamento);
                    });
                });
            $this->aplicarFiltro($todos, $filtro, null, $user);
            $todos = $todos->ordenBandeja()->get();

            return view('themes.backoffice.pages.tickets.index', $datos + [
                'todos'         => $todos,
                'acciones'      => $this->accionesPara($todos, $user),
                'departamento'  => $departamento,
                'departamentos' => Role::conTickets()->orderBy('name')->get(),
            ]);
        }

        // Un rechazado ya no es tarea del destino; lo que espera confirmación del origen va al final
        $recibidos = $this->consultaBandeja()->recibidosPor($user)
            ->where('estado', '!=', Ticket::ESTADO_RECHAZADO)
            ->orderByRaw("estado IN ('" . implode("','", Ticket::ESTADOS_POR_CONFIRMAR) . "')");
        $this->aplicarFiltro($recibidos, $filtro, 'recibidos', $user);
        $recibidos = $recibidos->ordenBandeja()->get();

        // Primero lo que el origen debe revisar
        $enviados = $this->consultaBandeja()->enviadosPor($user)
            ->orderByRaw("estado IN ('" . implode("','", Ticket::ESPERAN_AL_ORIGEN) . "') DESC");
        $this->aplicarFiltro($enviados, $filtro, 'enviados', $user);
        $enviados = $enviados->ordenBandeja()->get();

        return view('themes.backoffice.pages.tickets.index', $datos + [
            'recibidos'  => $recibidos,
            'enviados'   => $enviados,
            'acciones'   => $this->accionesPara($recibidos->merge($enviados), $user),
            'contadores' => Ticket::contadoresPara($user),
        ]);
    }

    /**
     * El administrador ve todos los departamentos salvo que marque "Solo los de mi rol"
     * (se recuerda en la sesión; aplica a la bandeja y al historial).
     */
    private function enSupervision(Request $request, $user)
    {
        if ($request->has('solo_mi_rol')) {
            session(['tickets.solo_mi_rol' => $request->boolean('solo_mi_rol')]);
        }

        return $user->supervisaTickets() && ! session('tickets.solo_mi_rol', false);
    }

    private function consultaBandeja()
    {
        return Ticket::activos()
            ->with(['rolOrigen', 'rolDestino', 'responsable', 'creador'])
            ->withCount('imagenes');
    }

    /** $bandeja: recibidos | enviados | null (supervisión). */
    private function aplicarFiltro($query, $filtro, $bandeja, $user)
    {
        switch ($filtro) {
            case 'urgentes':
                $query->where('prioridad', 'urgente');
                break;
            case 'sin_tomar':
                $query->where('estado', Ticket::ESTADO_PENDIENTE);
                break;
            case 'mios':
                $query->where($bandeja === 'recibidos' ? 'responsable_id' : 'creado_por', $user->id);
                break;
        }
    }

    /** Por ticket: acción del botón principal y si puede "tomarlo yo". */
    private function accionesPara($tickets, $user)
    {
        $policy = Gate::getPolicyFor(Ticket::class);

        return $tickets->mapWithKeys(function ($ticket) use ($policy, $user) {
            $disponibles = $policy->accionesDisponibles($user, $ticket);
            $principal = collect(Ticket::ACCIONES_PRINCIPALES)->first(function ($accion) use ($disponibles) {
                return in_array($accion, $disponibles);
            });

            return [$ticket->id => ['principal' => $principal, 'tomar' => $policy->tomar($user, $ticket)]];
        });
    }

    /** Finalizados y anulados, con filtros y paginación. */
    public function historial(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);
        $user = auth()->user();
        $supervision = $this->enSupervision($request, $user);

        $filtros = $request->validate([
            'desde'          => 'nullable|date_format:Y-m-d',
            'hasta'          => 'nullable|date_format:Y-m-d',
            'origen'         => 'nullable|integer',
            'destino'        => 'nullable|integer',
            'estado'         => ['nullable', Rule::in(Ticket::ESTADOS_CERRADOS)],
            'prioridad'      => ['nullable', Rule::in(array_keys(Ticket::PRIORIDADES))],
            'creador'        => 'nullable|integer',
            'responsable'    => 'nullable|integer',
            'ubicacion'      => 'nullable|string|max:150',
            'q'              => 'nullable|string|max:150',
        ]);

        // Lo que el usuario puede ver (sin los filtros): base también de las listas de personas
        $visibles = Ticket::cerrados()->when(! $supervision, function ($q) use ($user) {
            $q->visiblesPara($user);
        });

        $cierre = 'COALESCE(finalizado_at, anulado_at)';
        $query = (clone $visibles)
            ->with(['rolOrigen', 'rolDestino', 'responsable', 'creador'])
            ->withCount('imagenes')
            ->when($filtros['desde'] ?? null, function ($q, $desde) use ($cierre) { $q->whereRaw("$cierre >= ?", [$desde . ' 00:00:00']); })
            ->when($filtros['hasta'] ?? null, function ($q, $hasta) use ($cierre) { $q->whereRaw("$cierre <= ?", [$hasta . ' 23:59:59']); })
            ->when($filtros['origen'] ?? null, function ($q, $id) { $q->where('rol_origen_id', $id); })
            ->when($filtros['destino'] ?? null, function ($q, $id) { $q->where('rol_destino_id', $id); })
            ->when($filtros['estado'] ?? null, function ($q, $estado) { $q->where('estado', $estado); })
            ->when($filtros['prioridad'] ?? null, function ($q, $prioridad) { $q->where('prioridad', $prioridad); })
            ->when($filtros['creador'] ?? null, function ($q, $id) { $q->where('creado_por', $id); })
            ->when($filtros['responsable'] ?? null, function ($q, $id) { $q->where('responsable_id', $id); })
            // La columna es utf8mb4_unicode_ci: LIKE ya ignora mayúsculas y tildes
            ->when($filtros['ubicacion'] ?? null, function ($q, $texto) { $q->where('ubicacion', 'like', '%' . $texto . '%'); })
            ->when($filtros['q'] ?? null, function ($q, $texto) {
                // "TK-000123", "tk123" o "123" buscan por código; si no, por título
                if (preg_match('/^\s*(?:tk-?)?0*(\d+)\s*$/i', $texto, $m)) {
                    $q->where(function ($q) use ($m, $texto) {
                        $q->whereKey((int) $m[1])->orWhere('titulo', 'like', '%' . $texto . '%');
                    });
                } else {
                    $q->where('titulo', 'like', '%' . $texto . '%');
                }
            })
            ->orderByRaw("$cierre DESC")->orderByDesc('id');

        $tickets = $query->paginate(20)->appends(array_filter($filtros));

        $personas = function ($columna) use ($visibles) {
            $ids = (clone $visibles)->whereNotNull($columna)->distinct()->pluck($columna);

            return User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name']);
        };

        return view('themes.backoffice.pages.tickets.historial', [
            'tickets'       => $tickets,
            'filtros'       => $filtros,
            'hayFiltros'    => (bool) array_filter($filtros),
            'supervisa'     => $user->supervisaTickets(),
            'supervision'   => $supervision,
            'departamentos' => Role::conTickets()->orderBy('name')->get(),
            'creadores'     => $personas('creado_por'),
            'responsables'  => $personas('responsable_id'),
        ]);
    }

    /** Formulario de nuevo ticket, pensado para celular. */
    public function create()
    {
        $this->authorize('create', Ticket::class);

        return view('themes.backoffice.pages.tickets.create', [
            'misRoles'     => auth()->user()->rolesTickets(),
            'destinos'     => Role::conTickets()->orderBy('name')->get(),
            'sugerencias'  => Ticket::sugerenciasUbicacion(),
            'prioridades'  => Ticket::PRIORIDADES,
            'maxFotos'     => TicketService::MAX_FOTOS,
        ]);
    }

    public function store(Request $request, TicketService $tickets)
    {
        $this->authorize('create', Ticket::class);
        $user = auth()->user();
        $misRoles = $user->rolesTickets()->pluck('id')->all();

        // Con un solo departamento, el origen se asigna solo
        if (count($misRoles) === 1) {
            $request->merge(['rol_origen_id' => $misRoles[0]]);
        }

        // Ubicación libre: espacios repetidos fuera, para que no se dupliquen sugerencias
        if ($request->filled('ubicacion')) {
            $request->merge(['ubicacion' => preg_replace('/\s+/u', ' ', trim($request->input('ubicacion')))]);
        }

        $datos = $request->validate([
            'rol_origen_id'     => ['required', Rule::in($misRoles)],
            'rol_destino_id'    => ['required', 'different:rol_origen_id', Rule::exists('roles', 'id')->where('recibe_tickets', true)],
            'titulo'            => 'required|string|max:150',
            'descripcion'       => 'nullable|string|max:5000',
            'prioridad'         => ['required', Rule::in(array_keys(Ticket::PRIORIDADES))],
            'ubicacion'         => 'nullable|string|max:150',
        ] + $this->reglasFotos(), [
            'rol_origen_id.required'   => 'Elige con qué departamento envías el ticket.',
            'rol_origen_id.in'         => 'Elige con qué departamento envías el ticket.',
            'rol_destino_id.required'  => 'Elige a qué departamento va el ticket.',
            'rol_destino_id.different' => 'El ticket debe ir a un departamento distinto del tuyo.',
            'titulo.required'          => 'Escribe qué pasa (título).',
        ] + $this->mensajesFotos());

        $ticket = $tickets->crear(
            Arr::except($datos, ['fotos']),
            $request->file('fotos', []),
            $user
        );

        Alert::toast('Ticket ' . $ticket->codigo . ' enviado a ' . $ticket->rolDestino->name, 'success')->toToast('top');

        $destino = route('backoffice.tickets.show', $ticket);

        // El formulario envía por fetch (para no perder las fotos si falla la validación)
        return $request->wantsJson() ? response()->json(['redirect' => $destino]) : redirect($destino);
    }

    /** Detalle: datos, fotos, línea de tiempo, comentarios y acciones disponibles. */
    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);
        $user = auth()->user();

        $ticket->load(['rolOrigen', 'rolDestino', 'creador', 'responsable', 'imagenes', 'eventos.usuario', 'eventos.rol']);

        $policy = Gate::getPolicyFor(Ticket::class);
        $disponibles = $policy->accionesDisponibles($user, $ticket);
        $principal = collect(Ticket::ACCIONES_PRINCIPALES)->first(function ($accion) use ($disponibles) {
            return in_array($accion, $disponibles);
        });

        return view('themes.backoffice.pages.tickets.show', [
            'ticket'      => $ticket,
            'principal'   => $principal,
            // Las demás, en el orden del flujo; "solucionar" va con su formulario (nota y fotos)
            'secundarias' => array_values(array_diff($disponibles, [$principal])),
            'puedeTomar'  => $policy->tomar($user, $ticket),
            'puedeComentar' => $policy->comentar($user, $ticket),
            'fotos'       => $ticket->imagenes->groupBy('etapa'),
            'reenvioDestinos' => Role::conTickets()
                ->whereNotIn('id', [$ticket->rol_origen_id, $ticket->rol_destino_id])
                ->orderBy('name')->get(),
            'maxFotos'    => TicketService::MAX_FOTOS,
        ]);
    }

    /** Reglas para las fotos adjuntas (crear, solucionar, comentar). */
    private function reglasFotos()
    {
        return [
            'fotos'   => 'nullable|array|max:' . TicketService::MAX_FOTOS,
            'fotos.*' => 'image|mimes:jpeg,jpg,png,webp|max:12288',
        ];
    }

    private function mensajesFotos()
    {
        return [
            'fotos.max'     => 'Puedes adjuntar hasta ' . TicketService::MAX_FOTOS . ' fotos.',
            'fotos.*.image' => 'Una de las fotos no es una imagen válida.',
            'fotos.*.mimes' => 'Las fotos deben ser JPG, PNG o WEBP.',
            'fotos.*.max'   => 'Una de las fotos pesa más de 12 MB.',
        ];
    }

    /**
     * Ejecuta una acción de Ticket::ACCIONES o 'tomar'. La autorización se hace
     * dentro de TicketService, con la fila bloqueada.
     */
    public function accion(Request $request, Ticket $ticket, TicketService $tickets)
    {
        $acciones = array_diff(array_keys(Ticket::ACCIONES), ['autocerrar']);
        $request->validate([
            'accion'         => ['required', Rule::in(array_merge($acciones, ['tomar']))],
            'comentario'     => 'nullable|string|max:2000',
            'rol_destino_id' => 'nullable|integer',
        ] + $this->reglasFotos(), $this->mensajesFotos());
        $accion = $request->input('accion');

        try {
            $ticket = $accion === 'tomar'
                ? $tickets->tomar($ticket, auth()->user())
                : $tickets->ejecutarAccion($ticket, $accion, auth()->user(), $request->input('comentario'),
                    $request->only('rol_destino_id') + ['fotos' => $request->file('fotos', [])]);
        } catch (AuthorizationException $e) {
            return $this->respuestaAccion($request, false, 'No puedes hacer eso con este ticket.');
        } catch (\DomainException $e) {
            return $this->respuestaAccion($request, false, $e->getMessage());
        }

        $mensaje = $accion === 'tomar'
            ? 'Ahora eres responsable del ticket ' . $ticket->codigo . '.'
            : 'Ticket ' . $ticket->codigo . ': ' . $ticket->estado_etiqueta . '.';

        return $this->respuestaAccion($request, true, $mensaje);
    }

    /**
     * Formularios normales: toast y vuelta atrás. Por fetch (formularios con fotos):
     * JSON; si salió bien el toast queda en sesión para la página que se recarga.
     */
    private function respuestaAccion(Request $request, $ok, $mensaje)
    {
        if ($ok || ! $request->wantsJson()) {
            Alert::toast($mensaje, $ok ? 'success' : 'error')->toToast('top');
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => $ok, 'mensaje' => $mensaje], $ok ? 200 : 422);
        }

        return redirect()->back();
    }

    public function comentar(Request $request, Ticket $ticket, TicketService $tickets)
    {
        $this->authorize('comentar', $ticket);

        $request->validate([
            'comentario' => 'required_without:fotos|nullable|string|max:2000',
        ] + $this->reglasFotos(), [
            'comentario.required_without' => 'Escribe un comentario o adjunta una foto.',
        ] + $this->mensajesFotos());

        try {
            $tickets->comentar($ticket, auth()->user(), $request->input('comentario'), $request->file('fotos', []));
        } catch (AuthorizationException $e) {
            return $this->respuestaAccion($request, false, 'Ya no se puede comentar este ticket.');
        }

        return $this->respuestaAccion($request, true, 'Comentario agregado.');
    }

    /** Sirve una foto privada del ticket (storage/app, no pública). */
    public function imagen(Ticket $ticket, TicketImagen $imagen)
    {
        $this->authorize('view', $ticket);
        abort_unless((int) $imagen->ticket_id === (int) $ticket->id && Storage::exists($imagen->ruta), 404);

        return response()->file(Storage::path($imagen->ruta), ['Cache-Control' => 'private, max-age=86400']);
    }
}
