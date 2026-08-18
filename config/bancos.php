<?php

/**
 * Tabla oficial de códigos entregada por el banco (BancoEstado Empresas)
 * para el archivo de transferencias a terceros: Codigos_Banco.xlsx,
 * Codigos_Cuenta_Destino.xlsx, Codigos_Cuenta_Origen.xlsx.
 *
 * Los campos users.banco y users.tipo_cuenta_bancaria guardan estos
 * códigos directamente (no el nombre en texto libre), para que el CSV
 * de pago masivo se arme sin ninguna traducción/adivinanza de por medio.
 */
return [

    'bancos' => [
        '001' => 'BANCO DE CHILE',
        '009' => 'BANCO INTERNACIONAL',
        '014' => 'SCOTIABANK-DESARROLLO',
        '016' => 'BCI/MACHBANK',
        '027' => 'CORP-BANCA',
        '028' => 'BICE',
        '031' => 'HSBC BANK',
        '037' => 'BANCO SANTANDER',
        '039' => 'BANCO ITAU',
        '041' => 'JP MORGAN',
        '045' => 'MUFG BANK, LTD.',
        '049' => 'BANCO SECURITY',
        '051' => 'BANCO FALABELLA',
        '053' => 'BANCO RIPLEY/CHEK',
        '055' => 'BANCO CONSORCIO',
        '062' => 'TANNER',
        '504' => 'BANCO BBVA',
        '672' => 'COOPEUCH/DALE',
        '697' => 'LA POLAR PREPAGO',
        '699' => 'TRICOT PREPAGO',
        '729' => 'PREPAGO LOS HEROES',
        '730' => 'PREPAGO TENPO',
        '732' => 'TAPP CAJA LOS ANDES',
        '738' => 'GLOBAL66',
        '739' => 'AND CO.',
        '741' => 'COPEC PAY',
        '743' => 'PREX',
        '744' => 'SUM UP',
        '746' => 'FINTUAL',
        '747' => 'METRO MUV',
        '875' => 'MERCADO PAGO',
        '012' => 'BANCOESTADO',
    ],

    // Tipo de cuenta DESTINO (la del trabajador que recibe el pago)
    'tipos_cuenta_destino' => [
        'CCT'  => 'Cuenta Corriente',
        'CTV'  => 'Chequera Electrónica (Cuenta Vista)',
        'AHO'  => 'Cuenta de Ahorro',
        'CRUT' => 'Cuenta Rut (BancoEstado)',
    ],

    // Tipo de cuenta ORIGEN (la cuenta de la empresa, BancoEstado Empresas)
    'tipos_cuenta_origen' => [
        'CCT' => 'Cuenta Corriente',
        'CTV' => 'Chequera Electrónica (Cuenta Vista)',
    ],

];
