<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración SIRE (Sistema Integrado de Registros Electrónicos)
    |--------------------------------------------------------------------------
    |
    | Configuración para el sistema de comprobantes electrónicos SIRE
    |
    */

    // Series por defecto para comprobantes
    'factura_serie' => env('SIRE_FACTURA_SERIE', 'F001'),
    'boleta_serie' => env('SIRE_BOLETA_SERIE', 'B001'),
    'nota_credito_serie' => env('SIRE_NC_SERIE', 'NC01'),
    'nota_debito_serie' => env('SIRE_ND_SERIE', 'ND01'),

    // Sincronización automática
    'sincronizacion_activa' => env('SIRE_SYNC_ACTIVA', false),
    'sincronizacion_intervalo' => env('SIRE_SYNC_INTERVALO', 60), // minutos
    'ultima_sincronizacion' => null,

    // Configuración de reintentos
    'max_reintentos' => env('SIRE_MAX_REINTENTOS', 3),
    'tiempo_entre_reintentos' => env('SIRE_TIEMPO_REINTENTOS', 300), // segundos

    // Tipos de comprobantes
    'tipos_comprobantes' => [
        '01' => 'Factura',
        '03' => 'Boleta de Venta',
        '07' => 'Nota de Crédito',
        '08' => 'Nota de Débito',
        '09' => 'Guía de Remisión',
        '12' => 'Ticket de Máquina Registradora',
        '13' => 'Documento autorizado por SUNAT',
    ],

    // Tipos de documentos de identidad
    'tipos_documentos' => [
        '0' => 'DOC.TRIB.NO.DOM.SIN.RUC',
        '1' => 'Documento Nacional de Identidad',
        '4' => 'Carnet de Extranjería',
        '6' => 'Registro Único de Contribuyentes',
        '7' => 'Pasaporte',
        'A' => 'Cédula Diplomática de Identidad',
    ],

    // Códigos de moneda
    'monedas' => [
        'PEN' => 'Soles',
        'USD' => 'Dólares Americanos',
        'EUR' => 'Euros',
    ],

    // Tipos de afectación IGV
    'tipos_afectacion_igv' => [
        '10' => 'Gravado - Operación Onerosa',
        '11' => 'Gravado - Retiro por premio',
        '12' => 'Gravado - Retiro por donación',
        '13' => 'Gravado - Retiro',
        '14' => 'Gravado - Retiro por publicidad',
        '15' => 'Gravado - Bonificaciones',
        '16' => 'Gravado - Retiro por entrega a trabajadores',
        '17' => 'Gravado - IVAP',
        '20' => 'Exonerado - Operación Onerosa',
        '21' => 'Exonerado - Transferencia Gratuita',
        '30' => 'Inafecto - Operación Onerosa',
        '31' => 'Inafecto - Retiro por Bonificación',
        '32' => 'Inafecto - Retiro',
        '33' => 'Inafecto - Retiro por Muestras Médicas',
        '34' => 'Inafecto - Retiro por Convenio Colectivo',
        '35' => 'Inafecto - Retiro por premio',
        '36' => 'Inafecto - Retiro por publicidad',
        '40' => 'Exportación',
    ],

    // Libros electrónicos
    'tipos_libros' => [
        '010100' => 'Libro Caja y Bancos',
        '020100' => 'Libro de Ingresos y Gastos',
        '030100' => 'Libro de Inventarios y Balances',
        '040100' => 'Libro de Retenciones',
        '050100' => 'Libro Diario',
        '060100' => 'Libro Mayor',
        '070100' => 'Registro de Activos Fijos',
        '080100' => 'Registro de Compras',
        '080200' => 'Registro de Compras - Simplificado',
        '090100' => 'Registro de Consignaciones',
        '100100' => 'Registro de Costos',
        '120100' => 'Registro del Régimen de Percepciones',
        '130100' => 'Registro del Régimen de Retenciones',
        '140100' => 'Registro de Ventas e Ingresos',
        '140200' => 'Registro de Ventas e Ingresos - Simplificado',
    ],

    // Estados de comprobantes
    'estados' => [
        'pendiente' => 'Pendiente de envío',
        'enviado' => 'Enviado a SUNAT',
        'aceptado' => 'Aceptado por SUNAT',
        'rechazado' => 'Rechazado por SUNAT',
        'observado' => 'Observado por SUNAT',
        'anulado' => 'Anulado',
    ],
];
