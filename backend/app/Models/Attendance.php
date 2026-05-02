<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'check_in',
        'check_out',
        'total_hours',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'total_hours' => 'decimal:2',
            'status' => 'string',
        ];
    }

    /**
     * Relación con el modelo Employee.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

