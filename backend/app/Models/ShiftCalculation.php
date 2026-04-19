<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftCalculation extends Model
{
    protected $fillable = [
        'shift_id',
        'horas_diurnas',
        'horas_nocturnas',
        'horas_extra_diurnas',
        'horas_extra_nocturnas',
        'valor_total',
        'detalle_json',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'horas_diurnas' => 'decimal:2',
            'horas_nocturnas' => 'decimal:2',
            'horas_extra_diurnas' => 'decimal:2',
            'horas_extra_nocturnas' => 'decimal:2',
            'valor_total' => 'decimal:2',
            'detalle_json' => 'array',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Obtener el total de horas (suma de todos los tipos).
     */
    public function getTotalHoursAttribute(): float
    {
        return (float) round(
            (float) $this->horas_diurnas
            + (float) $this->horas_nocturnas
            + (float) $this->horas_extra_diurnas
            + (float) $this->horas_extra_nocturnas,
            2
        );
    }

    /**
     * Obtener el pago total calculado.
     */
    public function getTotalPayAttribute(): float
    {
        return (float) round((float) $this->valor_total, 2);
    }

    /**
     * Alias para detalle_json sugerido por la auditoría.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBreakdownAttribute(): array
    {
        return $this->detalle_json ?? [];
    }
}
