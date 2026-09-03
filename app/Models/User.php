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

#[Fillable(['name','email','phone','address','address_province','address_ward','address_detail','birth_date','password','role','is_active','avatar','google_id','admin_permissions'])]
#[Hidden(['password','two_factor_secret','two_factor_recovery_codes','remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at'=>'datetime',
            'password'=>'hashed',
            'role'=>UserRole::class,
            'is_active'=>'boolean',
            'admin_permissions'=>'array',
            'two_factor_confirmed_at'=>'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->is_active && $this->role === UserRole::ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->is_active && in_array($this->role,[UserRole::ADMIN,UserRole::STAFF],true);
    }

    public function hasAdminPermission(string $permission): bool
    {
        if (! $this->isAdmin()) return false;
        // Tài khoản admin gốc có toàn quyền quản trị.
        if ($this->name === 'admin') return true;
        return in_array($permission, $this->admin_permissions ?? [], true);
    }
}
