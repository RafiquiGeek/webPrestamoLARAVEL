<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TareaArchivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'tarea_id',
        'nombre_archivo',
        'ruta',
        'tipo_mime',
        'tamaño',
        'tipo',
        'subido_por',
    ];

    protected $casts = [
        'tamaño' => 'integer',
    ];

    protected $appends = ['url', 'es_imagen'];

    public function tarea()
    {
        return $this->belongsTo(Tarea::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function getUrlAttribute()
    {
        return Storage::url($this->ruta);
    }

    public function getEsImagenAttribute()
    {
        return $this->tipo === 'imagen';
    }

    public function getTamañoFormateadoAttribute()
    {
        $bytes = $this->tamaño;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        } else {
            return $bytes.' bytes';
        }
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($archivo) {
            if (! $archivo->tipo_mime && $archivo->ruta) {
                $extension = pathinfo($archivo->ruta, PATHINFO_EXTENSION);
                $archivo->tipo = self::determinarTipo($extension);
            }
        });

        static::deleting(function ($archivo) {
            if (Storage::exists($archivo->ruta)) {
                Storage::delete($archivo->ruta);
            }
        });
    }

    private static function determinarTipo($extension)
    {
        $imagenes = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp'];
        $documentos = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'];

        if (in_array(strtolower($extension), $imagenes)) {
            return 'imagen';
        } elseif (in_array(strtolower($extension), $documentos)) {
            return 'documento';
        } else {
            return 'otro';
        }
    }
}
