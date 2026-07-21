<?php

namespace App\Services;

/**
 * SiiBteService — Scraper BHE recibidas desde loa.sii.cl
 *
 * La respuesta de loa.sii.cl viene como JS con arrays:
 *   CantidadFilas = N;
 *   arr_informe_mensual['nroboleta_1'] = "51";
 *
 * SESIÓN: login único via consultarMultiplesMeses() para no saturar SII.
 * Compatible PHP 7.2+ (sin arrow functions, sin typed properties).
 */
class SiiBteService
{
    private $rut;
    private $dv;
    private $clave;
    private $cookieFile;
    private $sesionAbierta;

    const LOGIN_URL  = 'https://zeusr.sii.cl/cgi_AUT2000/CAutInicio.cgi';
    const LOGIN_PAGE = 'https://zeusr.sii.cl/AUT2000/InicioAutenticacion/IngresoRutClave.html';
    const BHE_FORM   = 'https://loa.sii.cl/cgi_IMT/TMBCOC_MenuConsultasContribRec.cgi';
    const BHE_MENS   = 'https://loa.sii.cl/cgi_IMT/TMBCOC_InformeMensualBheRec.cgi';
    const BHE_ANUAL  = 'https://loa.sii.cl/cgi_IMT/TMBCOC_InformeAnualBheRec.cgi';
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36';

    public function __construct()
    {
        $rutCompleto       = config('sii.rut_empresa', '');
        $partes            = explode('-', $rutCompleto);
        $this->rut         = $partes[0] ?? $rutCompleto;
        $this->dv          = $partes[1] ?? '0';
        $this->clave       = config('sii.clave_tributaria', '');
        $this->cookieFile  = '';
        $this->sesionAbierta = false;
    }

    // ── Sesión pública ──────────────────────────────────────────────────────

    public function abrirSesion()
    {
        if ($this->sesionAbierta) {
            return;
        }
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'sii_bhe_');
        $this->hacerLogin();
        $this->sesionAbierta = true;
    }

    public function cerrarSesion()
    {
        $this->limpiarCookies();
        $this->sesionAbierta = false;
    }

    public function consultarMensualConSesion($anio, $mes)
    {
        if (!$this->sesionAbierta) {
            throw new \Exception('Sesion no iniciada. Llamar abrirSesion() primero.');
        }
        $periodo = sprintf('%04d%02d', $anio, $mes);
        $html    = $this->pedirMensual($anio, $mes);
        return $this->parsearJs($html, $periodo);
    }

    /**
     * Login único, consulta todos los meses en la misma sesión.
     * @param  int   $anio
     * @param  int[] $meses
     * @param  int   $pausa  segundos entre consultas
     * @return array  [periodo => ['ok'=>bool, 'data'=>[], 'error'=>?string]]
     */
    public function consultarMultiplesMeses($anio, $meses, $pausa = 3)
    {
        $resultados = [];
        try {
            $this->abrirSesion();
            foreach ($meses as $i => $mes) {
                if ($i > 0 && $pausa > 0) {
                    sleep($pausa);
                }
                $periodo = sprintf('%04d%02d', $anio, $mes);
                try {
                    $data = $this->consultarMensualConSesion($anio, $mes);
                    $resultados[$periodo] = ['ok' => true, 'data' => $data, 'error' => null];
                } catch (\Exception $e) {
                    $resultados[$periodo] = ['ok' => false, 'data' => [], 'error' => $e->getMessage()];
                }
            }
        } finally {
            $this->cerrarSesion();
        }
        return $resultados;
    }

    // ── API retrocompatible (sesión por llamada) ─────────────────────────────

    public function consultarMensual($anio, $mes)
    {
        $periodo = sprintf('%04d%02d', $anio, $mes);
        try {
            $this->abrirSesion();
            $data = $this->consultarMensualConSesion($anio, $mes);
            return ['ok' => true, 'data' => $data, 'periodo' => $periodo, 'error' => null];
        } catch (\Exception $e) {
            return ['ok' => false, 'data' => [], 'periodo' => $periodo, 'error' => $e->getMessage()];
        } finally {
            $this->cerrarSesion();
        }
    }

    public function consultarAnual($anio)
    {
        $periodo = (string) $anio;
        try {
            $this->abrirSesion();
            $html = $this->pedirAnual($anio);
            $data = $this->parsearJs($html, $periodo);
            return ['ok' => true, 'data' => $data, 'periodo' => $periodo, 'error' => null];
        } catch (\Exception $e) {
            return ['ok' => false, 'data' => [], 'periodo' => $periodo, 'error' => $e->getMessage()];
        } finally {
            $this->cerrarSesion();
        }
    }

    public function credencialesConfiguradas()
    {
        return !empty($this->rut) && !empty($this->clave);
    }

    // ── Login ────────────────────────────────────────────────────────────────

    private function hacerLogin()
    {
        // GET previo para obtener cookies iniciales
        $this->curlGet(self::LOGIN_PAGE, '');

        // POST credenciales
        $loginResp = $this->curlPost(self::LOGIN_URL, http_build_query([
            'rut'        => $this->rut,
            'dv'         => $this->dv,
            'rutcntr'    => "{$this->rut}-{$this->dv}",
            'clave'      => $this->clave,
            'referencia' => self::BHE_FORM,
            '411'        => '',
        ]), [
            'Content-Type: application/x-www-form-urlencoded',
            'Referer: ' . self::LOGIN_PAGE,
        ]);

        // SII puede devolver HTTP 200 + JS redirect (no HTTP 302).
        // Solo fallamos si explícitamente aparece IngresoRutClave en la URL.
        if (strpos($loginResp['url'], 'IngresoRutClave') !== false) {
            throw new \Exception('Login SII fallido: credenciales incorrectas.');
        }

        // GET del formulario BHE — confirma que la sesion quedo establecida
        $formResp = $this->curlGet(self::BHE_FORM, 'https://loa.sii.cl/');

        if ($formResp['code'] !== 200) {
            throw new \Exception("Portal BHE HTTP {$formResp['code']}.");
        }

        if (strpos($formResp['url'], 'IngresoRutClave') !== false) {
            throw new \Exception('Sesion SII no establecida. Reintente en unos minutos.');
        }
    }

    // ── Consultas ────────────────────────────────────────────────────────────

    private function pedirMensual($anio, $mes)
    {
        $resp = $this->curlPost(self::BHE_MENS, http_build_query([
            'rut_arrastre'        => $this->rut,
            'dv_arrastre'         => $this->dv,
            'pagina_solicitada'   => '0',
            'cbmesinformemensual' => sprintf('%02d', $mes),
            'cbanoinformemensual' => $anio,
            'cmdconsultar1'       => 'Consultar',
        ]), [
            'Content-Type: application/x-www-form-urlencoded',
            'Referer: ' . self::BHE_FORM,
        ]);

        if ($resp['code'] !== 200) {
            throw new \Exception("HTTP {$resp['code']} en consulta mensual.");
        }
        if (strpos($resp['body'], 'IngresoRutClave') !== false) {
            throw new \Exception('Sesion expirada durante consulta mensual.');
        }
        return $resp['body'];
    }

    private function pedirAnual($anio)
    {
        $resp = $this->curlPost(self::BHE_ANUAL, http_build_query([
            'rut_arrastre'      => $this->rut,
            'dv_arrastre'       => $this->dv,
            'cbanoinformeanual' => $anio,
            'cmdconsultar12'    => 'Consultar',
        ]), [
            'Content-Type: application/x-www-form-urlencoded',
            'Referer: ' . self::BHE_FORM,
        ]);

        if ($resp['code'] !== 200) {
            throw new \Exception("HTTP {$resp['code']} en consulta anual.");
        }
        if (strpos($resp['body'], 'IngresoRutClave') !== false) {
            throw new \Exception('Sesion expirada durante consulta anual.');
        }
        return $resp['body'];
    }

    // ── Parser JS ────────────────────────────────────────────────────────────

    private function parsearJs($html, $periodo)
    {
        if (strpos($html, 'NO REGISTRA MOVIMIENTOS') !== false) {
            return [];
        }

        if (!preg_match('/CantidadFilas\s*=\s*(\d+)\s*;/', $html, $m)) {
            return [];
        }
        $total = (int) $m[1];
        if ($total === 0) {
            return [];
        }

        $btes = [];
        for ($i = 1; $i <= $total; $i++) {
            $folio        = $this->jsVal($html, 'nroboleta',        $i);
            $rutNum       = $this->jsVal($html, 'rutemisor',        $i);
            $dvEmisor     = $this->jsVal($html, 'dvemisor',         $i);
            $nombreEmisor = $this->jsVal($html, 'nombre_emisor',    $i);
            $fechaStr     = $this->jsVal($html, 'fecha_boleta',     $i);
            $estadoCod    = $this->jsVal($html, 'estado',           $i);

            $bruto    = $this->jsMiles($html, 'totalhonorarios',    $i);
            $retenido = $this->jsMiles($html, 'retencion_receptor', $i);
            $pagado   = $this->jsMiles($html, 'honorariosliquidos', $i);

            if (empty($folio)) {
                continue;
            }

            $rutEmisor = !empty($dvEmisor) ? "{$rutNum}-{$dvEmisor}" : $rutNum;
            $estado    = ($estadoCod === 'A') ? 'Anulada' : 'Vigente';

            $btes[] = [
                'folio'          => $folio,
                'periodo'        => $periodo,
                'estado'         => $estado,
                'rut_emisor'     => $rutEmisor,
                'nombre_emisor'  => $nombreEmisor,
                'fecha_emision'  => $this->parsearFecha($fechaStr),
                'fecha_pago'     => null,
                'monto_bruto'    => $bruto,
                'tasa_retencion' => 15.25,
                'monto_retenido' => $retenido,
                'monto_pagado'   => $pagado,
            ];
        }

        return $btes;
    }

    private function jsVal($html, $key, $idx)
    {
        $pat = '/arr_informe_mensual\[\'' . preg_quote($key, '/') . '_' . $idx . '\'\]\s*=\s*"([^"]*)"/';
        if (preg_match($pat, $html, $m)) {
            return $m[1];
        }
        return '';
    }

    private function jsMiles($html, $key, $idx)
    {
        $pat = '/arr_informe_mensual\[\'' . preg_quote($key, '/') . '_' . $idx . '\'\]\s*=\s*formatMiles\("(\d+)"/';
        if (preg_match($pat, $html, $m)) {
            return (int) $m[1];
        }
        return 0;
    }

    private function parsearFecha($valor)
    {
        $valor = trim($valor);
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $valor, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        return null;
    }

    // ── cURL ─────────────────────────────────────────────────────────────────

    private function curlPost($url, $body, $headers = [])
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_USERAGENT      => self::USER_AGENT,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            throw new \Exception("cURL error: {$err}");
        }
        return ['body' => $body, 'code' => $info['http_code'], 'url' => $info['url']];
    }

    private function curlGet($url, $referer = '')
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_USERAGENT      => self::USER_AGENT,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $referer ? ["Referer: {$referer}"] : [],
        ]);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            throw new \Exception("cURL error: {$err}");
        }
        return ['body' => $body, 'code' => $info['http_code'], 'url' => $info['url']];
    }

    private function limpiarCookies()
    {
        if (!empty($this->cookieFile) && file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
            $this->cookieFile = '';
        }
    }
}
