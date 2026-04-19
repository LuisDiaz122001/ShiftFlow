<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    public const ESTADO_ACTIVO = 'activo';

    public const ESTADO_INACTIVO = 'inactivo';

    protected $fillable = [
        'user_id',
        'nombre',
        'estado',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function resolveActiveContract(CarbonInterface $date): ?Contract
    {
        if ($this->relationLoaded('contracts')) {
            /** @var Contract|null $contract */
            $contract = $this->contracts
                ->filter(fn (Contract $contract): bool => $contract->isActiveAt($date))
                ->sortByDesc(fn (Contract $contract): string => $contract->fecha_inicio?->toDateString() ?? '')
                ->first();

            return $contract;
        }

        /** @var Contract|null $contract */
        $contract = $this->contracts()
            ->activeAt($date)
            ->orderByDesc('fecha_inicio')
            ->first();

        return $contract;
    }
}
