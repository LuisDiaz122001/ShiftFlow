<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'payroll_id',
        'user_id',
        'action',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log(int|null $payrollId, string $action, array $metadata = []): self
    {
        return self::create([
            'payroll_id' => $payrollId,
            'user_id' => auth()->id() ?? 1, // Default to admin system user if no auth
            'action' => $action,
            'ip_address' => request()->ip(),
            'metadata' => $metadata,
        ]);
    }
}
