<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $address_province
 * @property string|null $address_ward
 * @property string|null $address_detail
 * @property string|null $birth_date
 * @property UserRole $role
 * @property bool $is_active
 * @property string|null $avatar
 * @property string|null $google_id
 * @property Carbon|null $email_verified_at
 * @property string $password
 */
#[Fillable(['name', 'email', 'phone', 'address', 'address_province', 'address_ward', 'address_detail', 'birth_date', 'password', 'role', 'is_active', 'avatar', 'google_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->is_active && $this->role === UserRole::ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->is_active && in_array($this->role, [UserRole::ADMIN, UserRole::STAFF], true);
    }
}
