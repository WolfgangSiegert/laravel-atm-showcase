<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Operators are separate from ATM customers and cards.
class User extends Authenticatable
{
    use Notifiable;

    public const OPERATOR_ROLE_SUPERADMIN = 'superadmin';

    public const OPERATOR_ROLE_VIEWER = 'viewer';

    protected $fillable = ['name', 'email', 'password', 'is_operator', 'operator_role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_operator' => 'boolean'];
    }

    public function canManageAdministration(): bool
    {
        return $this->is_operator && $this->operator_role === self::OPERATOR_ROLE_SUPERADMIN;
    }

    public function isReadOnlyOperator(): bool
    {
        return $this->is_operator && $this->operator_role === self::OPERATOR_ROLE_VIEWER;
    }
}
