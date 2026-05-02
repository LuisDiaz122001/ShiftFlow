<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'employee_id',
        'payroll_cycle_id',
        'period_start',
        'period_end',
        'total_hours',
        'hourly_rate',
        'total_amount',
        'salario_base_pagado',
        'recargos_pagados',
        'deduccion_salud',
        'deduccion_pension',
        'total_pagado',
        'neto_pagado',
        'tipo_pago',
        'fecha_pago',
        'paid_at',
        'estado',
        'version',
        'calculation_snapshot',
        'closed_at',
        'created_by',
        'paid_by',
        'updated_by',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_hours' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'salario_base_pagado' => 'decimal:2',
            'recargos_pagados' => 'decimal:2',
            'deduccion_salud' => 'decimal:2',
            'deduccion_pension' => 'decimal:2',
            'total_pagado' => 'decimal:2',
            'neto_pagado' => 'decimal:2',
            'fecha_pago' => 'date',
            'paid_at' => 'datetime',
            'calculation_snapshot' => 'array',
            'closed_at' => 'datetime',
        ];
    }

    public function getStatusAttribute(): string
    {
        return $this->estado;
    }

    public function setStatusAttribute(string $value): void
    {
        $this->attributes['estado'] = $value;
    }

    public function markAsPaid(): void
    {
        $this->update([
            'estado' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['estado' => self::STATUS_CANCELLED]);
    }

    protected static function booted(): void
    {
        static::updating(function (Payroll $payroll) {
            // Immutability for PAID payrolls
            if ($payroll->getOriginal('estado') === self::STATUS_PAID) {
                PayrollLog::log($payroll->id, 'blocked_attempt', [
                    'reason' => 'Tentativa de modificar una nómina ya pagada.',
                    'dirty_fields' => $payroll->getDirty(),
                ]);
                throw new \RuntimeException('Integridad Financiera: Una nómina pagada (PAID) es absolutamente inmutable.');
            }

            // Existing logic for LOCKED status
            if ($payroll->getOriginal('estado') === self::STATUS_LOCKED) {
                if ($payroll->isDirty([
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
                ])) {
                    PayrollLog::log($payroll->id, 'blocked_attempt', [
                        'reason' => 'Tentativa de modificar campos protegidos en una nómina bloqueada.',
                    ]);
                    throw new \RuntimeException('Integridad Contable: Una nomina bloqueada (LOCKED) es inmutable.');
                }
            }
        });

        static::deleting(function (Payroll $payroll) {
            if ($payroll->estado === self::STATUS_LOCKED || $payroll->estado === self::STATUS_PAID) {
                PayrollLog::log($payroll->id, 'blocked_attempt', [
                    'reason' => 'Tentativa de eliminar una nómina bloqueada o pagada.',
                ]);
                throw new \RuntimeException('Integridad Contable: No se puede eliminar una nomina bloqueada o pagada.');
            }
        });
    }

    public function isLocked(): bool
    {
        return $this->estado === self::STATUS_LOCKED || $this->estado === self::STATUS_PAID;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PayrollCycle::class, 'payroll_cycle_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }

    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'payroll_shift');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PayrollLog::class);
    }
}
