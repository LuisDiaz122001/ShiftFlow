<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollCycle extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'fecha_pago',
        'estado',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_TRANSITIONS = [
        self::STATUS_OPEN => [self::STATUS_GENERATED],
        self::STATUS_GENERATED => [self::STATUS_CLOSED],
        self::STATUS_CLOSED => [], // Inmutable
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'fecha_pago' => 'date',
        ];
    }

    /**
     * Transiciona el estado del ciclo validando las reglas de negocio.
     */
    public function transitionTo(string $newStatus): void
    {
        $currentStatus = $this->estado;

        if ($currentStatus === $newStatus) {
            return;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new \RuntimeException("Transición de estado inválida: de {$currentStatus} a {$newStatus}.");
        }

        $this->estado = $newStatus;
        $this->save();
    }

    /**
     * Bloquea edición de turnos si el ciclo ya fue cerrado.
     */
    public function isLockedForEdition(): bool
    {
        return $this->estado === self::STATUS_CLOSED;
    }

    /**
     * Bloquea cálculos (automáticos) si el ciclo ya fue generado o cerrado.
     */
    public function isLockedForCalculation(): bool
    {
        return in_array($this->estado, [self::STATUS_GENERATED, self::STATUS_CLOSED], true);
    }

    public function shifts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function payrolls(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payroll::class);
    }
}
