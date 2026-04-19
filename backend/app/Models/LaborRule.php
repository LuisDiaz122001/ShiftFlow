<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaborRule extends Model
{
    protected $fillable = [
        'vigente_desde',
        'hora_diurna_inicio',
        'hora_nocturna_inicio',
        'recargo_nocturno',
        'recargo_dominical',
        'extra_diurna',
        'extra_nocturna',
        'porcentaje_salud',
        'porcentaje_pension',
        'horas_max_diarias',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vigente_desde' => 'date',
            'recargo_nocturno' => 'decimal:2',
            'recargo_dominical' => 'decimal:2',
            'extra_nocturna' => 'decimal:2',
            'porcentaje_salud' => 'decimal:2',
            'porcentaje_pension' => 'decimal:2',
            'horas_max_diarias' => 'decimal:2',
        ];
    }
}
