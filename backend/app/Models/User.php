<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, \Laravel\Sanctum\HasApiTokens;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_EMPLOYEE = 'employee';
    public const ROLE_SUPERVISOR = 'supervisor';

    /**
     * Comprueba si el usuario tiene el rol especificado.
     * Compatible con el campo 'role' de la tabla users.
     *
     * @param string|array $role
     */
    public function hasRole(string|array $role): bool
    {
        if (is_array($role)) {
            return in_array($this->role, $role, strict: true);
        }

        return $this->role === $role;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'salario_base' => 'decimal:2',
            'password' => 'hashed',
        ];
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }
}
