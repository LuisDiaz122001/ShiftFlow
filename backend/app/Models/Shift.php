<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shift extends Model
{
    use HasFactory;
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'employee_id',
        'fecha_inicio',
        'fecha_fin',
        'payroll_cycle_id',
        'status',
        'is_voided',
        'voided_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'voided_by',
        'voids_shift_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Regla de Oro: Prohibición total de eliminación (Zero-Delete)
        static::deleting(function (Shift $shift) {
            // Bypass en consola (Migraciones/Seeds), pero forzar en Tests y Web
            if (app()->runningInConsole() && !app()->environment('testing')) {
                return;
            }
            throw new \RuntimeException('Integridad Total: La eliminación física de turnos está prohibida. Use el proceso de Anulación (Void).');
        });

        // Regla de Integridad: Inmutabilidad de registros procesados
        static::updating(function (Shift $shift) {
            if (app()->runningInConsole() && !app()->environment('testing')) {
                return;
            }

            // Si ya no es pending, es inmutable (approved, rejected, voided)
            if ($shift->getOriginal('status') !== self::STATUS_PENDING) {
                // Solo permitimos el cambio de status (transición controlada) o anulación (is_voided)
                // Pero no cambios en fechas o empleado
                if ($shift->isDirty(['employee_id', 'fecha_inicio', 'fecha_fin'])) {
                    throw new \RuntimeException('Integridad Total: Un turno aprobado/rechazado es inmutable. Para corregir errores, anule y cree uno nuevo.');
                }
            }

            if ($shift->payrollCycle && $shift->payrollCycle->isLockedForEdition()) {
                throw new \RuntimeException('No se puede modificar un turno de un ciclo de nómina cerrado.');
            }
        });
    }

    public function approve(\App\Models\User $admin): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \RuntimeException('Solo se pueden aprobar turnos en estado pendiente.');
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
    }

    public function reject(\App\Models\User $admin): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \RuntimeException('Solo se pueden rechazar turnos en estado pendiente.');
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_by' => $admin->id,
            'rejected_at' => now(),
        ]);
    }

    public function void(\App\Models\User $admin): void
    {
        if ($this->status !== self::STATUS_APPROVED) {
            throw new \RuntimeException('Solo se pueden anular turnos previamente aprobados.');
        }

        if ($this->is_voided) {
            throw new \RuntimeException('Este turno ya ha sido anulado.');
        }

        $this->update([
            'is_voided' => true,
            'voided_by' => $admin->id,
            'voided_at' => now(),
        ]);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function calculation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ShiftCalculation::class);
    }

    public function payrollCycle(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PayrollCycle::class);
    }
}
