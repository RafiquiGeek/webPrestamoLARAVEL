<?php

namespace App\Helpers;

class NumeroALetras
{
    private static $UNIDADES = [
        '', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
        'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE',
        'VEINTE'
    ];

    private static $DECENAS = [
        'VEINTI', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'
    ];

    private static $CENTENAS = [
        'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
        'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'
    ];

    /**
     * Convierte un número a letras en español
     * 
     * @param float $numero El número a convertir
     * @param string $moneda La moneda (por defecto "SOLES")
     * @return string El número en letras
     */
    public static function convertir($numero, $moneda = 'SOLES')
    {
        $numero = number_format($numero, 2, '.', '');
        $partes = explode('.', $numero);
        $entero = (int)$partes[0];
        $decimales = (int)$partes[1];

        if ($entero == 0) {
            $resultado = 'CERO';
        } else {
            $resultado = self::convertirEntero($entero);
        }

        // Agregar "CON" y los céntimos
        $resultado .= ' CON ' . str_pad($decimales, 2, '0', STR_PAD_LEFT) . '/100 ' . $moneda;

        return strtoupper($resultado);
    }

    /**
     * Convierte la parte entera del número
     */
    private static function convertirEntero($numero)
    {
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
                $texto = 'UN MILLON';
            } else {
                $texto = self::convertirEntero($millones) . ' MILLONES';
            }

            if ($resto > 0) {
                $texto .= ' ' . self::convertirEntero($resto);
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
                $texto = self::convertirEntero($miles) . ' MIL';
            }

            if ($resto > 0) {
                $texto .= ' ' . self::convertirEntero($resto);
            }

            return $texto;
        }

        // Centenas
        if ($numero >= 100) {
            $centenas = intval($numero / 100);
            $resto = $numero % 100;

            if ($numero == 100) {
                return 'CIEN';
            }

            $texto = self::$CENTENAS[$centenas - 1];

            if ($resto > 0) {
                $texto .= ' ' . self::convertirEntero($resto);
            }

            return $texto;
        }

        // Decenas y unidades
        if ($numero >= 21) {
            $decenas = intval($numero / 10);
            $unidades = $numero % 10;

            $texto = self::$DECENAS[$decenas - 2];

            if ($unidades > 0) {
                $texto .= ' Y ' . self::$UNIDADES[$unidades];
            }

            return $texto;
        }

        // Del 0 al 20
        return self::$UNIDADES[$numero];
    }

    /**
     * Convierte solo a letras sin incluir moneda ni céntimos
     */
    public static function convertirSinMoneda($numero)
    {
        $numero = number_format($numero, 2, '.', '');
        $partes = explode('.', $numero);
        $entero = (int)$partes[0];
        $decimales = (int)$partes[1];

        if ($entero == 0) {
            $resultado = 'CERO';
        } else {
            $resultado = self::convertirEntero($entero);
        }

        if ($decimales > 0) {
            $resultado .= ' PUNTO ' . self::convertirEntero($decimales);
        }

        return strtoupper($resultado);
    }
}