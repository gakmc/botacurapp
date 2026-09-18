<?php

/**
 * Datos de pago de Botacura usados por el bot de WhatsApp.
 *
 * Fuente unica de verdad: tanto BotReservaController (que arma el mensaje
 * de confirmacion tras crear una reserva) como BotPromptService (que le da
 * al bot conocimiento general para responder preguntas antes de reservar)
 * leen de aqui, para que nunca queden desincronizados.
 */
return [

    'datos_bancarios_transferencia' => [
        'titular'            => 'CENTRO RECREATIVO BOTACURA LIMITADA',
        'rut'                => '77.848.621-0',
        'banco'              => 'Banco Estado',
        'tipo_cuenta'        => 'Cuenta Vista / Electrónica',
        'numero_cuenta'      => '36072963894',
        'correo_comprobante' => 'hola@botacura.cl',
    ],

];
