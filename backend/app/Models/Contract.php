<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    public const ESTADO_ACTIVO = 'activo';

    public const ESTADO_INACTIVO = 'inactivo';

    protected $fillable = [
        'employee_id',
        'salario_base',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'salario_base' => 'decimal:2',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @param Builder<Contract> $query
     * @return Builder<Contract>
     */
    public function scopeActiveAt(Builder $query, CarbonInterface $date): Builder
    {
        $dateString = $date->toDateString();

        return $query
            ->where('estado', self::ESTADO_ACTIVO)
            ->whereDate('fecha_inicio', '<=', $dateString)
            ->where(function (Builder $query) use ($dateString): void {
                $query
                    ->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $dateString);
            });
    }

    public function isActiveAt(CarbonInterface $date): bool
    {
        $dateString = $date->toDateString();
        $fechaInicio = $this->fecha_inicio?->toDateString();
        $fechaFin = $this->fecha_fin?->toDateString();

        if ($this->estado !== self::ESTADO_ACTIVO || $fechaInicio === null) {
            return false;
        }

        return $fechaInicio <= $dateString
            && ($fechaFin === null || $fechaFin >= $dateString);
    }
}
