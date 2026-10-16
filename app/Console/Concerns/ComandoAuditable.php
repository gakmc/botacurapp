<?php

namespace App\Console\Concerns;

use App\Auditoria;
use App\Services\AuditoriaService;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Audita la ejecución de un comando con UN registro resumen (sin detalle por fila).
 *
 *  - Con --programado (lo agrega el Kernel al agendarlo): actor "Sistema (comando)".
 *  - Sin --programado: ejecución manual; pide las credenciales de la plataforma
 *    (IdentificaOperador) y la registra a nombre de esa persona.
 *  - Con --dry-run: no modifica datos, no pide credenciales ni se audita.
 *
 * El resumen incluye cuántos registros se crearon/editaron/eliminaron (eventos de
 * modelos suprimidos) y los últimos mensajes info()/error() del comando.
 *
 * Opcional en el comando: protected $auditoriaCategoria = Auditoria::CATEGORIA_...;
 */
trait ComandoAuditable
{
    use IdentificaOperador;

    /** @var array Mensajes info()/error() para el resumen */
    protected $auditoriaMensajes = [];

    protected function configure()
    {
        parent::configure();

        $this->addOption('programado', null, InputOption::VALUE_NONE, 'Uso interno del scheduler: registra la ejecución a nombre del Sistema');
        $this->addOption('usuario', null, InputOption::VALUE_REQUIRED, 'Email de quien ejecuta manualmente (se pedirá la contraseña)');
    }

    public function info($string, $verbosity = null)
    {
        $this->capturarMensajeAuditoria($string);

        parent::info($string, $verbosity);
    }

    public function error($string, $verbosity = null)
    {
        $this->capturarMensajeAuditoria('Error: ' . $string);

        parent::error($string, $verbosity);
    }

    protected function capturarMensajeAuditoria($texto)
    {
        $texto = trim(preg_replace('/\s+/', ' ', strip_tags((string) $texto)));

        if ($texto !== '') {
            $this->auditoriaMensajes[] = Str::limit($texto, 200);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $servicio   = app(AuditoriaService::class);
        $simulacion = $this->hasOption('dry-run') && $this->option('dry-run');
        $programado = (bool) $this->option('programado');

        if ($simulacion) {
            return (int) parent::execute($input, $output);
        }

        if ($programado) {
            $servicio->establecerActor(null, Auditoria::ACTOR_SISTEMA, $this->getName());
        } elseif (! $this->identificarOperador()) {
            return 1;
        }

        $servicio->tomarConteo();
        $this->auditoriaMensajes = [];

        $codigo = 1;
        $error  = null;

        try {
            $codigo = $servicio->sinRegistro(function () use ($input, $output) {
                return parent::execute($input, $output);
            });
        } catch (Throwable $e) {
            $error = $e;
        }

        $this->registrarEjecucion($servicio, $programado, (int) $codigo, $error);

        if ($error) {
            throw $error;
        }

        return (int) $codigo;
    }

    protected function registrarEjecucion(AuditoriaService $servicio, $programado, $codigo, $error)
    {
        $verbos = [
            Auditoria::EVENTO_CREO    => 'creado(s)',
            Auditoria::EVENTO_EDITO   => 'editado(s)',
            Auditoria::EVENTO_ELIMINO => 'eliminado(s)',
        ];

        $partes = [];
        foreach ($servicio->tomarConteo() as $modelo => $eventos) {
            foreach ($eventos as $evento => $cantidad) {
                $partes[] = $cantidad . ' ' . $modelo . ' ' . ($verbos[$evento] ?? $evento);
            }
        }

        $cambios = [];
        if ($partes) {
            $cambios['Registros'] = [null, implode(', ', $partes)];
        }
        if ($this->auditoriaMensajes) {
            $cambios['Resultado'] = [null, implode(' | ', array_slice($this->auditoriaMensajes, -4))];
        }
        if ($error) {
            $cambios['Error'] = [null, Str::limit($error->getMessage(), 200)];
        } elseif ($codigo !== 0) {
            $cambios['Código de salida'] = [null, (string) $codigo];
        }

        $parametros = $this->parametrosAuditoria();
        if ($parametros !== '') {
            $cambios['Parámetros'] = [null, $parametros];
        }

        $servicio->registrar([
            'categoria'   => $this->auditoriaCategoria ?? Auditoria::CATEGORIA_FINANCIERA,
            'evento'      => Auditoria::EVENTO_EJECUTO,
            'descripcion' => ($programado ? 'ejecutó el proceso programado ' : 'ejecutó manualmente el comando ')
                . $this->getName() . ($error || $codigo !== 0 ? ' (terminó con error)' : ''),
            'cambios'     => $cambios,
        ]);
    }

    /** Argumentos y opciones explícitos, sin las opciones internas de auditoría. */
    protected function parametrosAuditoria()
    {
        $partes = [];

        foreach ($this->input->getArguments() as $nombre => $valor) {
            if ($nombre !== 'command' && ! is_int($nombre) && $valor !== $this->getName() && $valor !== null && $valor !== []) {
                $partes[] = $nombre . '=' . (is_array($valor) ? implode(',', $valor) : $valor);
            }
        }

        foreach ($this->input->getOptions() as $nombre => $valor) {
            if (in_array($nombre, ['programado', 'usuario', 'help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'env'], true)) {
                continue;
            }
            if ($valor === false || $valor === null || $valor === []) {
                continue;
            }
            $partes[] = '--' . $nombre . ($valor === true ? '' : '=' . (is_array($valor) ? implode(',', $valor) : $valor));
        }

        return Str::limit(implode(' ', $partes), 200);
    }
}
