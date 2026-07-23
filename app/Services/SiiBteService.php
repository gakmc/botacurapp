<?php

namespace App\Services;

/**
 * SiiBteService — Scraper BHE recibidas desde loa.sii.cl
 *
 * NOTA: "BTE" en el nombre es histórico. En realidad consulta
 * Boletas de Honorarios Electrónicas (BHE) recibidas.
 *
 * La respuesta de loa.sii.cl viene como JS con arrays:
 *   CantidadFilas = N;
 *   arr_informe_mensual['nroboleta_1'] = "51";
 *   arr_informe_mensual['rutemisor_1'] = "21447378";
 *   ...
 *
 * Compatible PHP 7.2+ (sin arrow functions, sin typed properties).
 */
class SiiBteService
{
    private $rut;
    private $dv;
    private $clave;
    private $cookieFile;

    const LOGIN_URL  = 'https://zeusr.sii.cl/cgi_AUT2000/CAutInicio.cgi';
    const BHE_FORM   = 'https://loa.sii.cl/cgi_IMT/TMBCOC_MenuConsultasContribRec.cgi';
    const BHE_MENS   = 'https://loa.sii.cl/cgi_IMT/TMBCOC_InformeMensualBheRec.cgi';
    const BHE_ANUAL  = 'https://loa.sii.cl/cgi_IMT/TMBCOC_InformeAnualBheRec.cgi';
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36';

    public function __construct()
    {
        $rutCompleto    = config('sii.rut_empresa', '');
        $partes         = explode('-', $rutCompleto);
        $this->rut      = $partes[0] ?? $rutCompleto;
        $this->dv       = $partes[1] ?? '0';
        $this->clave    = config('sii.clave_tributaria', '');
        $this->cookieFile = '';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API pública
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @return array{ok: bool, data: array, periodo: string, error: ?string}
     */
    public function consultarMensual(int $anio, int $mes)
    {
        $periodo = sprintf('%04d%02d', $anio, $mes);
        try {
            $this->iniciarSesion();
            $html = $this->pedirMensual($anio, $mes);
            $data = $this->parsearJs($html, $periodo);
            return ['ok' => true, 'data' => $data, 'periodo' => $periodo, 'error' => null];
        } catch (\Exception $e) {
            return ['ok' => false, 'data' => [], 'periodo' => $periodo, 'error' => $e->getMessage()];
        } finally {
            $this->limpiarCookies();
        }
    }

    /**
     * @return array{ok: bool, data: array, periodo: string, error: ?string}
     */
    public function consultarAnual(int $anio)
    {
        $periodo = (string) $anio;
        try {
            $this->iniciarSesion();
            $html = $this->pedirAnual($anio);
            $data = $this->parsearJs($html, $periodo);
            return ['ok' => true, 'data' => $data, 'periodo' => $periodo, 'error' => null];
        } catch (\Exception $e) {
            return ['ok' => false, 'data' => [], 'periodo' => $periodo, 'error' => $e->getMessage()];
        } finally {
            $this->limpiarCookies();
        }
    }

    public function credencialesConfiguradas()
    {
        return !empty($this->rut) && !empty($this->clave);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Privado: sesión
    // ─────────────────────────────────────────────────────────────────────────

    private function iniciarSesion()
    {
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'sii_bhe_');

        $loginResp = $this->curlPost(self::LOGIN_URL, http_build_query([
            'rut'        => $this->rut,
            'dv'         => $this->dv,
            'rutcntr'    => "{$this->rut}-{$this->dv}",
            'clave'      => $this->clave,
            'referencia' => self::BHE_FORM,
            '411'        => '',
        ]), [
            'Content-Type: application/x-www-form-urlencoded',
            'Referer: https://zeusr.sii.cl/AUT2000/InicioAutenticacion/IngresoRutClave.html',
        ]);

        // Login exitoso → SII redirige a la URL de referencia (loa.sii.cl)
        // Si la URL final sigue en zeusr.sii.cl, el login falló (credenciales o rate-limit)
        if (strpos($loginResp['url'], 'IngresoRutClave') !== false
            || strpos($loginResp['url'], 'zeusr.sii.cl') !== false) {
            throw new \Exception('Login SII fallido: credenciales incorrectas o portal temporalmente bloqueado.');
        }

        $formResp = $this->curlGet(self::BHE_FORM, 'https://loa.sii.cl/');

        if ($formResp['code'] !== 200) {
            throw new \Exception("No se pudo acceder al portal BHE (HTTP {$formResp['code']}).");
        }

        // Si el form GET redirigió de vuelta al login, la sesión no se estableció
        if (strpos($formResp['url'], 'sii.cl/AUT2000') !== false
            || strpos($formResp['url'], 'IngresoRutClave') !== false) {
            throw new \Exception('Sesión SII no establecida: el portal BHE rechazó la sesión (intente en unos minutos).');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Privado: consultas
    // ─────────────────────────────────────────────────────────────────────────

    private function pedirMensual(int $anio, int $mes)
    {
        $resp = $this->curlPost(self::BHE_MENS, http_build_query([
            'rut_arrastre'         => $this->rut,
            'dv_arrastre'          => $this->dv,
            'pagina_solicitada'    => '0',
            'cbmesinformemensual'  => sprintf('%02d', $mes),
            'cbanoinformemensual'  => $anio,
            'cmdconsultar1'        => 'Consultar',
        ]), [
            'Content-Type: application/x-www-form-urlencoded',
            'Referer: ' . self::BHE_FORM,
        ]);

        if ($resp['code'] !== 200) {
            throw new \Exception("Error HTTP {$resp['code']} en consulta BHE mensual.");
        }
        if (strpos($resp['body'], 'IngresoRutClave') !== false) {
            throw new \Exception('Sesión SII expiró durante la consulta mensual.');
        }
        return $resp['body'];
    }

    private function pedirAnual(int $anio)
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
            throw new \Exception("Error HTTP {$resp['code']} en consulta BHE anual.");
        }
        if (strpos($resp['body'], 'IngresoRutClave') !== false) {
            throw new \Exception('Sesión SII expiró durante la consulta anual.');
        }
        return $resp['body'];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Privado: parser JS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Parsea el JS embebido en la respuesta de loa.sii.cl.
     *
     * La respuesta contiene variables JS como:
     *   CantidadFilas=11;
     *   arr_informe_mensual['nroboleta_1'] = "51";
     *   arr_informe_mensual['rutemisor_1'] = "21447378";
     *   arr_informe_mensual['dvemisor_1']  = "K";
     *   arr_informe_mensual['nombre_emisor_1'] = "NOMBRE";
     *   arr_informe_mensual['fecha_boleta_1']  = "DD/MM/YYYY";
     *   arr_informe_mensual['totalhonorarios_1'] = formatMiles("135000",'.');
     *   arr_informe_mensual['retencion_receptor_1'] = formatMiles("20588",'.');
     *   arr_informe_mensual['honorariosliquidos_1'] = formatMiles("114412",'.');
     *   arr_informe_mensual['estado_1'] = "N";  // N=Vigente, A=Anulada
     *   arr_informe_mensual['es_soc_profesional_1'] = "NO";
     */
    private function parsearJs($html, $periodo)
    {
        // Sin datos
        if (strpos($html, 'NO REGISTRA MOVIMIENTOS') !== false) {
            return [];
        }

        // Extraer CantidadFilas
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
            $socProf      = $this->jsVal($html, 'es_soc_profesional', $i);

            // Montos están en formatMiles("N",...) — extraemos el número crudo
            $bruto    = $this->jsMiles($html, 'totalhonorarios',     $i);
            $retenido = $this->jsMiles($html, 'retencion_receptor',  $i);
            $pagado   = $this->jsMiles($html, 'honorariosliquidos',  $i);

            if (empty($folio)) continue;

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

    /**
     * Extrae valor de: arr_informe_mensual['key_N'] = "valor";
     */
    private function jsVal($html, $key, $idx)
    {
        $pattern = '/arr_informe_mensual\[\'' . preg_quote($key, '/') . '_' . $idx . '\'\]\s*=\s*"([^"]*)"/';
        if (preg_match($pattern, $html, $m)) {
            return $m[1];
        }
        return '';
    }

    /**
     * Extrae valor numérico de: arr_informe_mensual['key_N'] = formatMiles("12345",...);
     */
    private function jsMiles($html, $key, $idx)
    {
        $pattern = '/arr_informe_mensual\[\'' . preg_quote($key, '/') . '_' . $idx . '\'\]\s*=\s*formatMiles\("(\d+)"/';
        if (preg_match($pattern, $html, $m)) {
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

    // ─────────────────────────────────────────────────────────────────────────
    // Privado: HTTP cURL
    // ─────────────────────────────────────────────────────────────────────────

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
            CURLOPT_T