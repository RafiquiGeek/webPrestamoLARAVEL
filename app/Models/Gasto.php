<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gasto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'gastos';

    protected $fillable = [
        'categoria_gasto_id',
        'concepto',
        'descripcion',
        'monto',
        'fecha_gasto',
        'documento_identidad',
        'tipo_documento',
        'razon_social',
        'nombres',
        'apellidos',
        'tipo_comprobante',
        'serie_comprobante',
        'numero_comprobante',
        'usuario_registro',
        'observaciones',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_gasto' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relación con categoría de gasto
    public function categoria()
    {
        return $this->belongsTo(CategoriaGasto::class, 'categoria_gasto_id');
    }

    // Relación con usuario que registró
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_registro');
    }

    // Accessor para mostrar el tipo de documento completo
    public function getTipoDocumentoTextoAttribute()
    {
        $tipos = [
            'DNI' => 'DNI - Documento Nacional de Identidad',
            'RUC' => 'RUC - Registro Único de Contribuyentes',
            'CE' => 'CE - Carné de Extranjería',
            'PAS' => 'PAS - Pasaporte',
        ];

        return $tipos[$this->tipo_documento] ?? $this->tipo_documento;
    }

    // Accessor para mostrar el tipo de comprobante completo
    public function getTipoComprobanteTextoAttribute()
    {
        $tipos = [
            'factura' => 'Factura',
            'boleta' => 'Boleta de Venta',
            'recibo_honorarios' => 'Recibo por Honorarios',
            'ticket' => 'Ticket',
            'sin_documento' => 'Sin Documento de Pago',
        ];

        return $tipos[$this->tipo_comprobante] ?? $this->tipo_comprobante;
    }

    // Accessor para el número completo del comprobante
    public function getComprobanteCompletoAttribute()
    {
        if ($this->tipo_comprobante === 'sin_documento') {
            return 'Sin Documento';
        }

        return ($this->serie_comprobante ? $this->serie_comprobante.'-' : '').$this->numero_comprobante;
    }

    // Accessor para mostrar el nombre completo del beneficiario
    public function getBeneficiarioCompletoAttribute()
    {
        if (! empty($this->razon_social)) {
            return $this->razon_social;
        }

        return trim($this->nombres.' '.$this->apellidos);
    }

    // Scope para filtrar por categoría
    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('categoria_gasto_id', $categoriaId);
    }

    // Scope para filtrar por rango de fechas
    public function scopePorFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_gasto', [$fechaInicio, $fechaFin]);
    }

    // Scope para filtrar por tipo de documento
    public function scopePorTipoDocumento($query, $tipoDocumento)
    {
        return $query->where('tipo_documento', $tipoDocumento);
    }

    // Scope para filtrar por usuario
    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_registro', $usuarioId);
    }
}
