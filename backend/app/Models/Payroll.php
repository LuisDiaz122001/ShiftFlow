<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'payroll_cycle_id',
        'salario_base_pagado',
        'recargos_pagados',
        'deduccion_salud',
        'deduccion_pension',
        'total_pagado',
        'neto_pagado',
        'tipo_pago',
        'fecha_pago',
        'version',
        'calculation_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'salario_base_pagado' => 'decimal:2',
            'recargos_pagados' => 'decimal:2',
            'deduccion_salud' => 'decimal:2',
            'deduccion_pension' => 'decimal:2',
            'total_pagado' => 'decimal:2',
            'neto_pagado' => 'decimal:2',
            'fecha_pago' => 'date',
            'calculation_snapshot' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Payroll $payroll) {
            // Ajuste 4: Snapshot Inmutable.
            if ($payroll->isDirty('calculation_snapshot') && $payroll->getOriginal('calculation_snapshot') !== null) {
                throw new \RuntimeException('El snapshot de cálculo es inmutable y no puede modificarse.');
            }
        });
    }

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function cycle(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PayrollCycle::class, 'payroll_cycle_id');
    }

    public function details(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }
}
