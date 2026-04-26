<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaConfiguracion extends Model
{
    use HasFactory;

    protected $table = 'meta_configuracion';

    protected $fillable = [
        'umbral_morosidad',
    ];
}
