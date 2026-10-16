<?php

namespace App\Services;

use App\Auditoria;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Único punto de escritura de la auditoría.
 *
 * Registrado como singleton: el actor fijado manualmente (consola, webhooks)
 * y el request_id duran lo que dura el request o el proceso.
 *
 * Una falla al auditar nunca interrumpe la operación de negocio: se escribe
 * el payload en el canal de log 'auditoria' (storage/logs/auditoria/).
 */
class AuditoriaService
{
    /** @var User|null */
    protected $actor;

    /** @var string|null */
    protected $actorTipo;

    /** @var string|null */
    protected $actorDetalle;

    /** @var string|null */
    protected $requestId;

    /** @var bool */
    protected $omitido = false;

    /** @var int Suspensiones anidadas (sinRegistro) */
    protected $suspendido = 0;

    /** @var array Caché de nombres de registros relacionados: [clase@columna][id] => nombre */
    protected $nombres = [];

    /** @var array Modelos creados en este request/proceso: [clase#id] => id de auditoría */
    protected $creados = [];

    /** @var array Eventos suprimidos por sinRegistro(): [Modelo][evento] => cantidad */
    protected $conteo = [];

    // CONTEXTO

    /**
     * Fija quién realiza las acciones cuando no hay sesión web
     * (comandos de consola, webhooks, procesos programados).
     */
    public function establecerActor($user, $tipo, $detalle = null)
    {
        $this->actor        = $user;
        $this->actorTipo    = $tipo;
        $this->actorDetalle = $detalle;

        return $this;
    }

    /**
     * Registra a nombre de un usuario concreto (ej. eventos de login/logout,
     * donde la sesión aún no existe o ya se cerró) sin alterar el actor actual.
     */
    public function registrarComo($user, array $datos, $tipo = Auditoria::ACTOR_USUARIO)
    {
        $anterior = [$this->actor, $this->actorTipo, $this->actorDetalle];

        $this->establecerActor($user, $tipo, $this->actorDetalle);

        try {
            return $this->registrar($datos);
        } finally {
            list($this->actor, $this->actorTipo, $this->actorDetalle) = $anterior;
        }
    }

    /** Agrega una nota al actor actual. Ej: "Sistema (IoT Home Assistant, sin API key)". */
    public function agregarDetalleActor($texto)
    {
        $this->actorDetalle = $this->actorDetalle ? $this->actorDetalle . ', ' . $texto : $texto;

        return $this;
    }

    /**
     * Deja el servicio como al inicio de un request. Se usa antes de cada job de
     * la cola: un worker es un proceso de larga duración y no debe arrastrar
     * actor, request_id ni cachés de un job al siguiente.
     */
    public function reiniciarContexto()
    {
        $this->limpiarActor();
        $this->omitido    = false;
        $this->suspendido = 0;
        $this->requestId  = null;
        $this->creados    = [];
        $this->conteo     = [];
        $this->nombres    = [];

        return $this;
    }

    public function limpiarActor()
    {
        $this->actor        = null;
        $this->actorTipo    = null;
        $this->actorDetalle = null;

        return $this;
    }

    /**
     * Desactiva el registro para el resto del request/proceso (ej. rutas del bot).
     */
    public function omitir($omitir = true)
    {
        $this->omitido = (bool) $omitir;

        return $this;
    }

    public function estaOmitido()
    {
        return $this->omitido || $this->suspendido > 0;
    }

    /**
     * Ejecuta $operacion sin registrar los eventos automáticos de los modelos.
     * Se usa cuando la acción se audita con un único registro resumido.
     */
    public function sinRegistro(callable $operacion)
    {
        $this->suspendido++;

        try {
            return $operacion();
        } finally {
            $this->suspendido--;
        }
    }

    /**
     * Cuenta un evento de modelo que no se registró por estar dentro de
     * sinRegistro() (ej. las filas que crea un comando). No cuenta lo omitido (bot).
     */
    public function contarSuprimido(Model $modelo, $evento)
    {
        if ($this->omitido || $this->suspendido === 0) {
            return;
        }

        $nombre = class_basename($modelo);
        $this->conteo[$nombre][$evento] = ($this->conteo[$nombre][$evento] ?? 0) + 1;
    }

    /** Devuelve y reinicia el conteo de eventos suprimidos. */
    public function tomarConteo()
    {
        $conteo       = $this->conteo;
        $this->conteo = [];

        return $conteo;
    }

    /**
     * Para bloques que borran y vuelven a crear registros (ubicaciones, wellness,
     * desayuno/once): ejecuta la operación sin eventos individuales y registra un
     * solo cambio "antes → después", únicamente si el resultado cambió.
     *
     * $describir devuelve el estado como texto legible (ej. "Tinaja 3 (4 personas)").
     */
    public function sincronizar(Model $auditable, $categoria, $etiqueta, $descripcion, callable $describir, callable $operacion)
    {
        $antes     = $this->describirSeguro($describir);
        $resultado = $this->sinRegistro($operacion);
        $despues   = $this->describirSeguro($describir);

        $cambio = [$etiqueta => [$antes ?: 'Ninguno', $despues ?: 'Ninguno']];

        // Si el registro padre se creó en este mismo request, se completa su creación
        if ($antes !== $despues && ! $this->fusionarEnCreacion($auditable, $cambio)) {
            $this->registrar([
                'categoria'   => $categoria,
                'evento'      => Auditoria::EVENTO_EDITO,
                'auditable'   => $auditable,
                'contexto'    => $auditable,
                'descripcion' => $descripcion,
                'cambios'     => $cambio,
            ]);
        }

        return $resultado;
    }

    /**
     * Atajo de sincronizar() para colecciones de una reserva que se borran y
     * vuelven a crear: 'ubicaciones', 'wellness', 'desayuno_once'.
     */
    public function sincronizarReserva($reserva, $que, callable $operacion)
    {
        $textos = [
            'ubicaciones'   => ['Ubicaciones', 'cambió las ubicaciones de '],
            'wellness'      => ['Wellness', 'cambió el wellness de '],
            'desayuno_once' => ['Desayuno/Once', 'cambió el desayuno/once de '],
        ];
        list($etiqueta, $frase) = $textos[$que] ?? [ucfirst($que), 'cambió ' . $que . ' de '];

        return $this->sincronizar(
            $reserva,
            Auditoria::CATEGORIA_OPERACIONAL,
            $etiqueta,
            $frase . $this->textoReserva($reserva),
            function () use ($reserva, $que) {
                return $reserva->resumenAuditoria($que);
            },
            $operacion
        );
    }

    protected function describirSeguro(callable $describir)
    {
        try {
            return (string) $describir();
        } catch (Throwable $e) {
            return '';
        }
    }

    public function requestId()
    {
        if (is_null($this->requestId)) {
            $this->requestId = (string) Str::uuid();
        }

        return $this->requestId;
    }

    // REGISTRO

    /**
     * Registra una acción.
     *
     * $datos:
     *  - categoria   (requerido) Auditoria::CATEGORIA_*
     *  - evento      (requerido) Auditoria::EVENTO_* u otro verbo corto
     *  - descripcion (requerido) frase sin sujeto ni fecha: "creó la reserva #512 para el cliente Juan Pérez"
     *  - auditable   (opcional)  Model sobre el que se actuó
     *  - contexto    (opcional)  Model padre que agrupa la acción (ej. la Reserva de un pedido)
     *  - cambios     (opcional)  ['Etiqueta' => [antes, después], ...] solo campos no sensibles
     *  - user_id / user_nombre (opcional) para eventos sin sesión, ej. login fallido
     *
     * @return Auditoria|null
     */
    public function registrar(array $datos)
    {
        if ($this->estaOmitido()) {
            return null;
        }

        $payload = [];

        try {
            $payload = $this->construirPayload($datos);

            if (in_array($payload['actor_tipo'], (array) config('auditoria.actores_omitidos', []), true)) {
                return null;
            }

            return Auditoria::create($payload);
        } catch (Throwable $e) {
            $this->reportarFalla($payload ?: $this->payloadMinimo($datos), $e);

            return null;
        }
    }

    public function recordarCreacion(Model $modelo, Auditoria $registro)
    {
        $this->creados[get_class($modelo) . '#' . $modelo->getKey()] = $registro->id;
    }

    /**
     * Si el modelo se creó en este mismo request (ej. create() seguido de update()
     * en el mismo controlador), la edición se incorpora al registro de creación
     * en vez de generar un "editó" adicional. Devuelve true si se fusionó.
     */
    public function fusionarEnCreacion(Model $modelo, array $cambios)
    {
        // Sin auditoría activa no hay nada que completar
        if ($this->estaOmitido()) {
            return true;
        }

        $clave = get_class($modelo) . '#' . $modelo->getKey();

        if (! isset($this->creados[$clave])) {
            return false;
        }

        try {
            $fila = Auditoria::query()->whereKey($this->creados[$clave])->first(['id', 'cambios']);
            if (! $fila) {
                return false;
            }

            $actuales = $fila->cambios ?: [];
            foreach ($this->limpiarCambios($cambios) ?: [] as $etiqueta => $valores) {
                if ($valores[1] === null) {
                    unset($actuales[$etiqueta]);
                } else {
                    $actuales[$etiqueta] = [null, $valores[1]];
                }
            }

            // Query builder: el modelo Auditoria bloquea las ediciones
            Auditoria::query()->whereKey($fila->id)->update([
                'cambios' => $actuales ? json_encode($actuales, JSON_UNESCAPED_UNICODE) : null,
            ]);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    protected function construirPayload(array $datos)
    {
        list($user, $tipo, $detalle) = $this->resolverActor();

        $payload = [
            'user_id'        => $user ? $user->id : (isset($datos['user_id']) ? $datos['user_id'] : null),
            'user_nombre'    => $user ? $user->name : (isset($datos['user_nombre']) ? $datos['user_nombre'] : null),
            'user_roles'     => $user ? Str::limit($user->list_roles(), 250, '') : null,
            'actor_tipo'     => $tipo,
            'actor_detalle'  => $detalle ? Str::limit($detalle, 250, '') : null,
            'categoria'      => $datos['categoria'],
            'evento'         => $datos['evento'],
            'auditable_type' => null,
            'auditable_id'   => null,
            'descripcion'    => $datos['descripcion'],
            'cambios'        => $this->limpiarCambios(isset($datos['cambios']) ? $datos['cambios'] : null),
            'ip'             => null,
            'ruta_nombre'    => null,
            'request_id'     => $this->requestId(),
        ];

        if (isset($datos['auditable']) && $datos['auditable'] instanceof Model) {
            $payload['auditable_type'] = get_class($datos['auditable']);
            $payload['auditable_id']   = $datos['auditable']->getKey();
        }

        $payload['contexto_type'] = null;
        $payload['contexto_id']   = null;
        if (isset($datos['contexto']) && $datos['contexto'] instanceof Model && $datos['contexto']->getKey()) {
            $payload['contexto_type'] = get_class($datos['contexto']);
            $payload['contexto_id']   = $datos['contexto']->getKey();
        }

        if (! app()->runningInConsole()) {
            $request                = request();
            $payload['ip']          = $request->ip();
            $payload['ruta_nombre'] = optional($request->route())->getName();
        }

        return $payload;
    }

    /**
     * @return array [User|null, string tipo, string|null detalle]
     */
    protected function resolverActor()
    {
        if (! is_null($this->actorTipo)) {
            return [$this->actor, $this->actorTipo, $this->actorDetalle];
        }

        $user = $this->usuarioAutenticado();
        if ($user) {
            return [$user, Auditoria::ACTOR_USUARIO, null];
        }

        if (app()->runningInConsole()) {
            return [null, Auditoria::ACTOR_CONSOLA, 'sin identificar, usuario SO: ' . $this->usuarioSistemaOperativo()];
        }

        return [null, Auditoria::ACTOR_INVITADO, null];
    }

    protected function usuarioAutenticado()
    {
        try {
            $user = auth()->user();

            if (! $user && ! app()->runningInConsole() && request()->bearerToken()) {
                $user = auth('api')->user();
            }

            return $user instanceof User ? $user : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function usuarioSistemaOperativo()
    {
        $usuario = getenv('USERNAME') ?: getenv('USER');

        return $usuario ?: get_current_user();
    }

    /**
     * Descarta claves sensibles y normaliza valores a texto.
     */
    protected function limpiarCambios($cambios)
    {
        if (empty($cambios) || ! is_array($cambios)) {
            return null;
        }

        $sensibles = array_map('strtolower', (array) config('auditoria.campos_sensibles', []));
        $limpios   = [];

        foreach ($cambios as $campo => $valores) {
            if (in_array(Str::snake(strtolower($campo)), $sensibles, true)) {
                continue;
            }

            $valores = (array) $valores;
            $limpios[$campo] = [
                $this->valorTexto(isset($valores[0]) ? $valores[0] : null),
                $this->valorTexto(isset($valores[1]) ? $valores[1] : null),
            ];
        }

        return $limpios ?: null;
    }

    protected function valorTexto($valor)
    {
        if (is_null($valor)) {
            return null;
        }
        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }
        if ($valor instanceof Carbon) {
            return $valor->format('d-m-Y H:i');
        }
        if (is_scalar($valor)) {
            return Str::limit((string) $valor, 250);
        }

        return Str::limit(json_encode($valor), 250);
    }

    protected function payloadMinimo(array $datos)
    {
        $auditable = isset($datos['auditable']) && $datos['auditable'] instanceof Model ? $datos['auditable'] : null;

        return [
            'categoria'      => isset($datos['categoria']) ? $datos['categoria'] : null,
            'evento'         => isset($datos['evento']) ? $datos['evento'] : null,
            'descripcion'    => isset($datos['descripcion']) ? $datos['descripcion'] : null,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id'   => $auditable ? $auditable->getKey() : null,
            'request_id'     => $this->requestId,
        ];
    }

    protected function reportarFalla(array $payload, Throwable $e)
    {
        try {
            $payload['fecha_original'] = Carbon::now()->toDateTimeString();

            Log::channel('auditoria')->error('No se pudo registrar la auditoría', [
                'error'   => $e->getMessage(),
                'archivo' => $e->getFile() . ':' . $e->getLine(),
                'payload' => $payload,
            ]);
        } catch (Throwable $ignorada) {
            // Si ni el log está disponible, no se interrumpe la operación.
        }
    }

    // FORMATO

    /**
     * "01-10-2026 a las 09:00" (24h) o "01-10-2026 a las 9:00 am" (12h).
     */
    public function fechaHora($fecha)
    {
        $fecha   = $fecha instanceof Carbon ? $fecha : Carbon::parse($fecha);
        $formato = config('auditoria.formato_hora') === '12h' ? 'g:i a' : 'H:i';

        return $fecha->format('d-m-Y') . ' a las ' . $fecha->format($formato);
    }

    public function fecha($fecha)
    {
        if (empty($fecha)) {
            return null;
        }

        return ($fecha instanceof Carbon ? $fecha : Carbon::parse($fecha))->format('d-m-Y');
    }

    public function monto($monto)
    {
        return '$' . number_format((float) $monto, 0, ',', '.');
    }

    public function hora($hora)
    {
        if (empty($hora)) {
            return null;
        }

        return ($hora instanceof Carbon ? $hora : Carbon::parse($hora))->format('H:i');
    }

    /**
     * "la reserva #512 de Juan Pérez (05-10-2026)"
     */
    public function textoReserva($reserva)
    {
        if (! $reserva || ! $reserva->getKey()) {
            return 'una reserva';
        }

        $cliente = optional($reserva->cliente)->nombre_cliente;
        $fecha   = $this->fecha($reserva->getAttributes()['fecha_visita'] ?? null);

        return 'la reserva #' . $reserva->getKey()
            . ($cliente ? ' de ' . $cliente : '')
            . ($fecha ? ' (' . $fecha . ')' : '');
    }

    /**
     * Nombre legible de un registro relacionado, con caché por request.
     * Ej: nombreRelacionado(Producto::class, 'nombre', 12) => "Pisco Sour"
     */
    public function nombreRelacionado($clase, $columna, $id)
    {
        if (is_null($id) || $id === '') {
            return null;
        }

        $clave = $clase . '@' . $columna;

        if (! isset($this->nombres[$clave]) || ! array_key_exists($id, $this->nombres[$clave])) {
            $nombre = null;
            try {
                $nombre = $clase::query()->whereKey($id)->value($columna);
            } catch (Throwable $e) {
                // se muestra el id
            }
            $this->nombres[$clave][$id] = $nombre ?: ('#' . $id);
        }

        return $this->nombres[$clave][$id];
    }
}
