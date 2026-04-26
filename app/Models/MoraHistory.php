<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoraHistory extends Model
{
    protected $table = 'moras_history';

    protected $guarded = ['id'];

    public $timestamps = false; // Deshabilitar timestamps automáticos

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mora()
    {
        return $this->belongsTo(Mora::class);
    }
}
