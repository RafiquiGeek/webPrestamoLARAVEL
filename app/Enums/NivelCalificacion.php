<?php

namespace App\Enums;

enum NivelCalificacion: string
{
    case NINGUNO = 'Ninguno';
    case BRONCE = 'Bronce';
    case PLATA = 'Plata';
    case ORO = 'Oro';

    public function label(): string
    {
        return match($this) {
            self::NINGUNO => 'Ninguno',
            self::BRONCE => 'Bronce',
            self::PLATA => 'Plata',
            self::ORO => 'Oro',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NINGUNO => 'secondary',
            self::BRONCE => 'warning',
            self::PLATA => 'info',
            self::ORO => 'success',
        };
    }

    public function hexColor(): string
    {
        return match($this) {
            self::NINGUNO => '#6c757d',
            self::BRONCE => '#cd7f32',
            self::PLATA => '#94a3b8',
            self::ORO => '#ffd700',
        };
    }

    public function icono(): string
    {
        return match($this) {
            self::NINGUNO => 'fas fa-minus-circle',
            self::BRONCE => 'fas fa-medal',
            self::PLATA => 'fas fa-award',
            self::ORO => 'fas fa-trophy',
        };
    }
}
