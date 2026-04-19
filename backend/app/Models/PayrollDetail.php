<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollDetail extends Model
{
    public const TYPE_EARNING = 'earning';
    public const TYPE_DEDUCTION = 'deduction';

    protected $fillable = [
        'payroll_id',
        'concept',
        'label',
        'type',
        'hours',
        'rate',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'rate' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function payroll(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
