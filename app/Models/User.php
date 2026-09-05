<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name','email','phone','address','address_province','address_ward','address_detail','birth_date','password','role','is_active','avatar','google_id','admin_permissions','registration_pending','created_by_user_id','approved_by_user_id','approved_at','approval_status'])]
#[Hidden(['password','two_factor_secret','two_factor_recovery_codes','remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at'=>'datetime',
            'password'=>'hashed',
            'role'=>UserRole::class,
            'is_active'=>'boolean',
            'registration_pending'=>'boolean',
            'admin_permissions'=>'array',
            'two_factor_confirmed_at'=>'datetime',
            'approved_at'=>'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(self::class, 'approved_by_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->is_active && $this->role === UserRole::ADMIN;
    }

    public function isBackoffice(): bool
    {
        return $this->is_active && in_array($this->role, [UserRole::ADMIN, UserRole::SENIOR_STAFF, UserRole::STAFF], true);
    }

    public function isSeniorStaff(): bool
    {
        return $this->is_active && in_array($this->role, [UserRole::SENIOR_STAFF, UserRole::STAFF], true);
    }

    public function isStaff(): bool
    {
        return $this->is_active && in_array($this->role, array_merge([UserRole::ADMIN], UserRole::employeeRoles()), true);
    }

    public function hasAdminPermission(string $permission): bool
    {
        if (! $this->isAdmin()) return false;
        if ($this->name === 'admin') return true;
        return in_array($permission, $this->admin_permissions ?? [], true);
    }
}
