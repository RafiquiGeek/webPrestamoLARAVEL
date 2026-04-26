<?php

namespace App\Helpers;

class CarteraHelper
{
    /**
     * Obtener la clase de badge para el estado del préstamo
     *
     * @param  string  $estado
     * @return string
     */
    public static function estadoBadge($estado)
    {
        switch ($estado) {
            case 'Vigente': return 'success';
            case 'Moroso': return 'danger';
            case 'Nueva Solicitud': return 'info';
            case 'Por Desembolsar': return 'primary';
            case 'Pagado': return 'secondary';
            case 'Cancelado': return 'dark';
            default: return 'light';
        }
    }

    /**
     * Obtener la clase de badge para el estado de la cuota
     *
     * @param  string  $estado
     * @return string
     */
    public static function cuotaBadge($estado)
    {
        switch ($estado) {
            case 'Pagado': return 'success';
            case 'Parcial': return 'warning';
            case 'Pendiente': return 'danger';
            default: return 'secondary';
        }
    }
}
