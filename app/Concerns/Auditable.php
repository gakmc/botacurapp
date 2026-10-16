<?php

namespace App\Concerns;

use App\Auditoria;
use App\Services\AuditoriaService;
use Carbon\Carbon;
use Throwable;

/**
 * Registra en la auditoría las altas, ediciones y eliminaciones del modelo.
 *
 * El modelo declara:
 *
 *   protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;
 *
 *   // Lista blanca: solo estos campos aparecen en "cambios". Nunca incluir datos sensibles.
 *   protected $auditoriaCampos = [
 *       'cantidad'    => 'Cantidad',
 *       'subtotal'    => ['Subtotal', 'monto'],
 *       'id_producto' => ['Producto', 'App\Producto@nombre'],
 *   ];
 *   Formatos: 'monto', 'fecha', 'hora', 'fechahora', 'bool', 'Clase@columna' (nombre del relacionado).
 *
 *   // Opcional: campos sensibles de los que solo se registra QUE cambiaron, sin valores.
 *   protected $auditoriaCamposOcultos = ['password' => 'Contraseña'];
 *
 *   // Opcional: frase sin sujeto ni fecha. Ej: "agregó 2 x Pisco Sour al consumo de la reserva #512..."
 *   protected function auditoriaDescripcion($evento) { ... }
 *
 *   // Opcional (catálogos): frase genérica  →  creó el producto "Pisco Sour"
 *   protected $auditoriaNombre = 'el producto';
 *
 *   // Opcional: registro padre para el historial agrupado (ej. la Reserva).
 *   protected function auditoriaContexto() { return $this->reserva; }
 *
 * Una edición que no toca ningún campo de la lista blanca no se registra.
 * Las escrituras con query builder (where()->update(), relación()->delete(),
 * increment) no disparan eventos y deben auditarse explícitamente.
 */
trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($modelo) {
            $modelo->registrarAuditoria(Auditoria::EVENTO_CREO);
        });

        static::updated(function ($modelo) {
            $modelo->registrarAuditoria(Auditoria::EVENTO_EDITO);
        });

        static::deleted(function ($modelo) {
            $modelo->registrarAuditoria(Auditoria::EVENTO_ELIMINO);
        });
    }

    // RELACIONES

    public function auditorias()
    {
        return $this->morphMany(Auditoria::class, 'auditable')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    // REGISTRO

    public function registrarAuditoria($evento)
    {
        $servicio = app(AuditoriaService::class);

        if ($servicio->estaOmitido()) {
            // Solo se cuenta (para resúmenes de comandos), sin formatear valores
            $relevantes = ($this->auditoriaCampos ?? []) + ($this->auditoriaCamposOcultos ?? []);
            if ($evento !== Auditoria::EVENTO_EDITO || array_intersect_key($this->getDirty(), $relevantes)) {
                $servicio->contarSuprimido($this, $evento);
            }

            return;
        }

        $cambios = $this->cambiosAuditables($evento);

        if ($evento === Auditoria::EVENTO_EDITO && empty($cambios)) {
            return;
        }

        // Creado y editado en el mismo request: se completa el registro de creación
        if ($evento === Auditoria::EVENTO_EDITO && $servicio->fusionarEnCreacion($this, $cambios)) {
            return;
        }

        $registro = $servicio->registrar([
            'categoria'   => $this->auditoriaCategoria ?? Auditoria::CATEGORIA_OPERACIONAL,
            'evento'      => $evento,
            'auditable'   => $this,
            'contexto'    => $this->contextoAuditoriaSeguro(),
            'descripcion' => $this->descripcionAuditoriaSegura($evento),
            'cambios'     => $cambios,
        ]);

        if ($registro && $evento === Auditoria::EVENTO_CREO) {
            $servicio->recordarCreacion($this, $registro);
        }
    }

    /**
     * Registro padre que agrupa la acción. El modelo lo define con
     * auditoriaContexto() (ej. un pedido devuelve su Reserva).
     */
    protected function contextoAuditoriaSeguro()
    {
        try {
            return method_exists($this, 'auditoriaContexto') ? $this->auditoriaContexto() : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Cambios de la lista blanca ya formateados: ['Etiqueta' => [antes, después]].
     */
    protected function cambiosAuditables($evento)
    {
        $cambios = [];

        try {
            $campos = $this->auditoriaCampos ?? [];

            if ($evento === Auditoria::EVENTO_EDITO) {
                $sucios = array_intersect_key($this->getDirty(), $campos);

                foreach ($sucios as $campo => $nuevo) {
                    list($etiqueta, $formato) = $this->especificacionCampo($campos[$campo]);

                    $antes   = $this->formatearValorAuditoria($this->getOriginal($campo), $formato);
                    $despues = $this->formatearValorAuditoria($nuevo, $formato);

                    if ($antes !== $despues) {
                        $cambios[$etiqueta] = [$antes, $despues];
                    }
                }

                // Campos sensibles: se registra que cambiaron, nunca su valor
                $ocultos = $this->auditoriaCamposOcultos ?? [];
                foreach (array_keys(array_intersect_key($this->getDirty(), $ocultos)) as $campo) {
                    $cambios[$ocultos[$campo]] = ['(oculto)', 'modificado'];
                }

                return $cambios;
            }

            // Alta o baja: valores no vacíos de la lista blanca
            $atributos = $this->getAttributes();

            foreach ($campos as $campo => $especificacion) {
                if (! array_key_exists($campo, $atributos) || $atributos[$campo] === null || $atributos[$campo] === '') {
                    continue;
                }

                list($etiqueta, $formato) = $this->especificacionCampo($especificacion);
                $valor = $this->formatearValorAuditoria($atributos[$campo], $formato);

                $cambios[$etiqueta] = $evento === Auditoria::EVENTO_ELIMINO ? [$valor, null] : [null, $valor];
            }
        } catch (Throwable $e) {
            return [];
        }

        return $cambios;
    }

    protected function especificacionCampo($especificacion)
    {
        return is_array($especificacion)
            ? [$especificacion[0], $especificacion[1] ?? null]
            : [$especificacion, null];
    }

    protected function formatearValorAuditoria($valor, $formato)
    {
        if (is_null($valor) || $valor === '') {
            return null;
        }

        $servicio = app(AuditoriaService::class);

        switch ($formato) {
            case 'monto':
                return $servicio->monto($valor);
            case 'fecha':
                return $servicio->fecha($valor);
            case 'hora':
                return $servicio->hora($valor);
            case 'fechahora':
                return ($valor instanceof Carbon ? $valor : Carbon::parse($valor))->format('d-m-Y H:i');
            case 'bool':
                return $valor ? 'Sí' : 'No';
        }

        if ($formato && strpos($formato, '@') !== false) {
            list($clase, $columna) = explode('@', $formato, 2);

            return $servicio->nombreRelacionado($clase, $columna, $valor);
        }

        return is_scalar($valor) ? (string) $valor : json_encode($valor);
    }

    // DESCRIPCIÓN

    protected function descripcionAuditoriaSegura($evento)
    {
        try {
            if (method_exists($this, 'auditoriaDescripcion')) {
                $texto = $this->auditoriaDescripcion($evento);
                if ($texto) {
                    return $texto;
                }
            }
        } catch (Throwable $e) {
            // Se usa la descripción genérica
        }

        // Catálogos: protected $auditoriaNombre = 'el producto'  →  creó el producto "Pisco Sour"
        if (! empty($this->auditoriaNombre)) {
            return $this->verboAuditoria($evento) . ' ' . $this->auditoriaNombre . ' "' . $this->tituloAuditoria() . '"';
        }

        return $this->verboAuditoria($evento) . ' ' . class_basename($this) . ' #' . $this->getKey();
    }

    /** Nombre visible del registro para las frases genéricas. */
    public function tituloAuditoria()
    {
        foreach (['nombre', 'nombre_programa', 'nombre_servicio', 'nombre_cliente', 'name'] as $columna) {
            if (! empty($this->getAttributes()[$columna])) {
                return $this->getAttributes()[$columna];
            }
        }

        return '#' . $this->getKey();
    }

    protected function verboAuditoria($evento)
    {
        $verbos = [
            Auditoria::EVENTO_CREO    => 'creó',
            Auditoria::EVENTO_EDITO   => 'editó',
            Auditoria::EVENTO_ELIMINO => 'eliminó',
        ];

        return $verbos[$evento] ?? $evento;
    }

    /**
     * Elige el verbo según el evento. Ej: $this->segunEvento($evento, 'agregó', 'editó', 'eliminó')
     */
    protected function segunEvento($evento, $creo, $edito, $elimino)
    {
        if ($evento === Auditoria::EVENTO_CREO) {
            return $creo;
        }

        return $evento === Auditoria::EVENTO_ELIMINO ? $elimino : $edito;
    }

    protected function textoReservaAuditoria($reserva)
    {
        return app(AuditoriaService::class)->textoReserva($reserva);
    }
}
