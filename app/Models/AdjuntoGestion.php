<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AdjuntoGestion extends Model
{
    use HasFactory;

    protected $table = 'adjuntos_gestiones';

    protected $fillable = [
        'gestion_id',
        'nombre_archivo',
        'nombre_archivo_sistema',
        'ruta_archivo',
        'tipo_archivo',
        'extension',
        'tamaño',
        'descripcion',
        'subido_por',
    ];

    protected $casts = [
        'tamaño' => 'integer',
    ];

    // protected $appends = [
    //     'url_archivo',
    // ];

    // Constantes para tipos de archivo
    const TIPO_FOTO = 'foto';

    const TIPO_DOCUMENTO = 'documento';

    const TIPO_AUDIO = 'audio';

    const TIPO_VIDEO = 'video';

    /**
     * Relación con la gestión a la que pertenece el adjunto
     */
    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class);
    }

    /**
     * Relación con el usuario que subió el archivo
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    /**
     * Obtiene el texto del tipo de archivo
     */
    public function getTipoArchivoTextoAttribute(): string
    {
        return match ($this->tipo_archivo) {
            self::TIPO_FOTO => 'Fotografía',
            self::TIPO_DOCUMENTO => 'Documento',
            self::TIPO_AUDIO => 'Audio',
            self::TIPO_VIDEO => 'Video',
            default => 'Desconocido'
        };
    }

    /**
     * Obtiene el tamaño formateado
     */
    public function getTamañoFormateadoAttribute(): string
    {
        $bytes = $this->tamaño;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        } else {
            return $bytes.' bytes';
        }
    }

    /**
     * Obtiene la URL del archivo
     */
    public function getUrlArchivoAttribute(): string
    {
        return Storage::url($this->ruta_archivo);
    }

    /**
     * Verifica si el archivo existe físicamente
     */
    public function getExisteAttribute(): bool
    {
        return Storage::exists($this->ruta_archivo);
    }

    /**
     * Verifica si es una imagen
     */
    public function getEsImagenAttribute(): bool
    {
        return $this->tipo_archivo === self::TIPO_FOTO ||
               in_array(strtolower($this->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Obtiene el ícono según el tipo de archivo
     */
    public function getIconoAttribute(): string
    {
        return match ($this->tipo_archivo) {
            self::TIPO_FOTO => 'fas fa-image',
            self::TIPO_DOCUMENTO => 'fas fa-file-alt',
            self::TIPO_AUDIO => 'fas fa-file-audio',
            self::TIPO_VIDEO => 'fas fa-file-video',
            default => 'fas fa-file'
        };
    }

    /**
     * Scope para archivos de tipo específico
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo_archivo', $tipo);
    }

    /**
     * Scope para fotos únicamente
     */
    public function scopeFotos($query)
    {
        return $query->where('tipo_archivo', self::TIPO_FOTO);
    }

    /**
     * Scope para documentos únicamente
     */
    public function scopeDocumentos($query)
    {
        return $query->where('tipo_archivo', self::TIPO_DOCUMENTO);
    }

    /**
     * Elimina el archivo físico al eliminar el registro
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($adjunto) {
            if (Storage::exists($adjunto->ruta_archivo)) {
                Storage::delete($adjunto->ruta_archivo);
            }
        });
    }
}
