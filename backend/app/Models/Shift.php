<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shift extends Model
{
    protected $fillable = [
        'user_id',
        'employee_id',
        'fecha_inicio',
        'fecha_fin',
        'payroll_cycle_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollCycle(): BelongsTo
    {
        return $this->belongsTo(PayrollCycle::class);
    }

    public function calculation(): HasOne
    {
        return $this->hasOne(ShiftCalculation::class);
    }
}
