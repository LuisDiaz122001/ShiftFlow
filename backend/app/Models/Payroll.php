<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'employee_id',
        'fecha_inicio',
        'fecha_fin',
        'total_hours',
        'diurnas_hours',
        'nocturnas_hours',
        'total_pago',
        'estado',
        'audit_shift_ids',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'total_hours' => 'decimal:2',
            'diurnas_hours' => 'decimal:2',
            'nocturnas_hours' => 'decimal:2',
            'total_pago' => 'decimal:2',
            'audit_shift_ids' => 'array',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Payroll $payroll) {
            if ($payroll->getOriginal('estado') === self::STATUS_LOCKED) {
                // Only allow changing from LOCKED to PAID if that's allowed, 
                // but user said "no puede editarse ni recalcularse".
                // We'll allow status change to PAID but block everything else.
                if ($payroll->isDirty(['total_hours', 'total_pago', 'fecha_inicio', 'fecha_fin', 'employee_id'])) {
                    throw new \RuntimeException('Integridad Contable: Una nómina bloqueada (LOCKED) es inmutable.');
                }
            }
        });

        static::deleting(function (Payroll $payroll) {
            if ($payroll->estado === self::STATUS_LOCKED) {
                throw new \RuntimeException('Integridad Contable: No se puede eliminar una nómina bloqueada.');
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

    public function shifts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'payroll_shift');
    }
}
