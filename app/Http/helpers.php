<?php

use App\Models\Cliente;
use App\Models\Tasa;

/**
 * Calcula el pago periódico de un préstamo
 */
function pago($tasa, $nper, $va)
{
    $tasa = $tasa / 100;
    return ($va * $tasa) / (1 - pow(1 + $tasa, -$nper));
}

/**
 * Convierte un porcentaje a decimal
 */
function porcentaje($porcentaje)
{
    return $porcentaje / 100;
}

/**
 * Realiza cálculos financieros para un préstamo
 */
function realizar_calculo($pres, $sem)
{
    $prestamo = $pres;
    $semanas = $sem;

    $tasa_semanal = Tasa::where('id', 1)->value('valor');
    $tasa_semanal_porcentaje = porcentaje($tasa_semanal);

    switch ($semanas) {
        case 20:
            $tasa_interes = 1.28;
            break;
        case 18:
            $tasa_interes = 1.27;
            break;
        case 15:
            $tasa_interes = 1.25;
            break;
        case 12:
            $tasa_interes = 1.04;
            break;
        default:
            echo 'No reconozco esos números';
            exit();
            break;
    }

    $tasa_interes_semanal = round((pow(1 + $tasa_interes, 1 / $semanas) - 1) * 100, 2);
    $tasa_interes_semanal_porcetaje = porcentaje($tasa_interes_semanal);

    $total_total = $prestamo * (1 + $tasa_interes_semanal_porcetaje);

    $com_porcentaje = $tasa_interes_semanal_porcetaje - $tasa_semanal_porcentaje;
    $com = $tasa_interes_semanal - $tasa_semanal;

    $valor_cuota_correcto = number_format(round(pago($tasa_interes_semanal, $semanas, $prestamo), 2), 2);

    $interes = round(($prestamo * $tasa_semanal_porcentaje) / 1.18, 2);
    $comision = round(($prestamo * $com_porcentaje) / 1.18, 2);
    $igv = round(($interes + $comision) * 0.18, 2);

    $pago_capital = $valor_cuota_correcto - $interes - $comision - $igv;

    $saldo_capital = round($prestamo - $pago_capital);

    return [
        'prestamo' => $prestamo,
        'semanas' => $semanas,
        'tasa_semanal' => $tasa_semanal,
        'tasa_semanal_porcentaje' => $tasa_semanal_porcentaje,
        'tasa_interes' => $tasa_interes,
        'tasa_interes_semanal' => $tasa_interes_semanal,
        'tasa_interes_semanal_porcetaje' => $tasa_interes_semanal_porcetaje,
        'com_porcentaje' => $com_porcentaje,
        'com' => $com,
        'valor_cuota_correcto' => $valor_cuota_correcto,
        'interes' => $interes,
        'comision' => $comision,
        'igv' => $igv,
        'pago_capital' => $pago_capital,
        'saldo_capital' => $saldo_capital,
    ];
}

/**
 * Verifica si existe un DNI en la base de datos
 */
function verificar_dni($dni)
{
    return Cliente::join('personas', 'persona_id', '=', 'personas.id')
        ->where('documento', $dni)->count();
}

/**
 * Convierte un número a letras en español (versión mejorada y completa)
 * 
 * @param float $numero El número a convertir
 * @param string $moneda La moneda (por defecto "SOLES")
 * @return string El número en letras
 */
if (!function_exists('numeroALetras')) {
    function numeroALetras($numero, $moneda = 'SOLES')
    {
        $numero = floatval($numero);
        $entero = intval($numero);
        $decimales = intval(round(($numero - $entero) * 100));

        if ($entero == 0) {
            $resultado = 'CERO';
        } else {
            $resultado = convertirEnteroALetras($entero);
        }

        // Agregar "CON" y los céntimos
        $resultado .= ' CON ' . str_pad($decimales, 2, '0', STR_PAD_LEFT) . '/100 ' . $moneda;

        return strtoupper($resultado);
    }
}

/**
 * Convierte la parte entera del número a letras
 * 
 * @param int $numero
 * @return string
 */
if (!function_exists('convertirEnteroALetras')) {
    function convertirEnteroALetras($numero)
    {
        $unidades = [
            '', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
            'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE',
            'VEINTE'
        ];

        $decenas = [
            'VEINTI', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'
        ];

        $centenas = [
            'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
            'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'
        ];

        if ($numero == 0) {
            return 'CERO';
        }

        if ($numero == 1) {
            return 'UNO';
        }

        // Millones
        if ($numero >= 1000000) {
            $millones = intval($numero / 1000000);
            $resto = $numero % 1000000;

            if ($millones == 1) {
                $texto = 'UN MILLÓN';
            } else {
                $texto = convertirEnteroALetras($millones) . ' MILLONES';
            }

            if ($resto > 0) {
                $texto .= ' ' . convertirEnteroALetras($resto);
            }

            return $texto;
        }

        // Miles
        if ($numero >= 1000) {
            $miles = intval($numero / 1000);
            $resto = $numero % 1000;

            if ($miles == 1) {
                $texto = 'MIL';
            } else {
                $texto = convertirEnteroALetras($miles) . ' MIL';
            }

            if ($resto > 0) {
                $texto .= ' ' . convertirEnteroALetras($resto);
            }

            return $texto;
        }

        // Centenas
        if ($numero >= 100) {
            $centenas_index = intval($numero / 100);
            $resto = $numero % 100;

            if ($numero == 100) {
                return 'CIEN';
            }

            $texto = $centenas[$centenas_index - 1];

            if ($resto > 0) {
                $texto .= ' ' . convertirEnteroALetras($resto);
            }

            return $texto;
        }

        // Decenas y unidades
        if ($numero >= 21) {
            $decenas_index = intval($numero / 10);
            $unidades_resto = $numero % 10;

            $texto = $decenas[$decenas_index - 2];

            if ($unidades_resto > 0) {
                $texto .= ' Y ' . $unidades[$unidades_resto];
            }

            return $texto;
        }

        // Del 0 al 20
        return $unidades[$numero];
    }
}

/**
 * Convierte un número a letras sin incluir moneda
 * 
 * @param float $numero
 * @return string
 */
if (!function_exists('numeroALetrasSinMoneda')) {
    function numeroALetrasSinMoneda($numero)
    {
        $numero = floatval($numero);
        $entero = intval($numero);
        $decimales = intval(round(($numero - $entero) * 100));

        if ($entero == 0) {
            $resultado = 'CERO';
        } else {
            $resultado = convertirEnteroALetras($entero);
        }

        if ($decimales > 0) {
            $resultado .= ' CON ' . str_pad($decimales, 2, '0', STR_PAD_LEFT) . '/100';
        }

        return strtoupper($resultado);
    }
}

/**
 * Alias de numeroALetras para compatibilidad con código existente
 * 
 * @param float $numero
 * @param string $moneda
 * @return string
 */
if (!function_exists('NumeroALetras')) {
    function NumeroALetras($numero, $moneda = 'SOLES')
    {
        return numeroALetras($numero, $moneda);
    }
}