<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operacion extends Model
{
    use HasFactory;

    protected $casts = [
        'fecha' => 'datetime',
    ];

    protected $table = 'operaciones';

    protected $fillable = [
        'cliente_id',
        'prestamo_id',
        'convenio_id',
        'cuenta_id',
        'fecha',
        'metodo_pago_id',
        'abono',
        'tipo_operacion',
        'gestion_id',
        'operacion_general_id',
        'codigo',
        'nro_operacion',
        'fecha_operacion',
        'entidad_bancaria',
        'voucher_path', // Cambiado de 'ruta_voucher' a 'voucher_path'
        'user_id',
        'comentario',
        'estado_rendicion',
        'justificacion_edicion',
        'editado_por',
        'editado_en',
        'justificacion_anulacion',
        'anulado_por',
        'anulado_en',
        'estado',
        // Campos de validación
        'estado_validacion',
        'observaciones_validacion',
        'validado_por',
        'validado_en',
        'observado_por',
        'observado_en',
    ];

    protected $attributes = [
        'estado_rendicion' => 'pendiente',
    ];

    // Resto del código del modelo (relaciones y métodos) permanece igual
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function operacionesCuota(): HasMany
    {
        return $this->hasMany(OperacionCuota::class);
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function metodoDePago()
    {
        return $this->belongsTo(MetodoDePago::class, 'metodo_pago_id');
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class);
    }

    public function cuotas()
    {
        return $this->belongsToMany(Cuota::class, 'operaciones_cuota')->withTimestamps();
    }

    public function morasCuota()
    {
        return $this->belongsToMany(
            MoraCuota::class,
            'operacion_mora', // Tabla pivot
            'operacion_id',   // FK de operacion en la pivot
            'mora_cuota_id'   // FK de mora_cuota en la pivot
        )->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function editadoPor()
    {
        return $this->belongsTo(User::class, 'editado_por');
    }

    public function anuladoPor()
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    public function validadoPor()
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    public function observadoPor()
    {
        return $this->belongsTo(User::class, 'observado_por');
    }

    public function carteraAnalista()
    {
        return $this->belongsTo(CarteraAnalista::class);
    }

    public function carteraAsesor()
    {
        return $this->belongsTo(CarteraAsesor::class);
    }

    public function carteraJcc()
    {
        return $this->belongsTo(CarteraJcc::class);
    }

    public function calcularIGV()
    {
        $tasaIGV = 0.18;

        return $this->abono * $tasaIGV;
    }

    public function tipoOperacionMora()
    {
        return $this->tipo_operacion == 'Pago de mora';
    }

    public function operacionGeneral()
    {
        return $this->belongsTo(Operacion::class, 'operacion_general_id');
    }

    public function operacionesRelacionadas()
    {
        return $this->hasMany(Operacion::class, 'operacion_general_id');
    }
}
