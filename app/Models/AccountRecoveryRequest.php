<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AccountRecoveryRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'method',
        'status',
        'otp_hash',
        'otp_expires_at',
        'otp_verified_at',
        'otp_attempts',
        'approved_by_user_id',
        'approved_at',
        'rejected_by_user_id',
        'rejected_at',
        'review_note',
    ];

    protected $hidden = ['otp_hash'];

    protected function casts(): array
    {
        return [
            'otp_expires_at' => 'datetime',
            'otp_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'otp_attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function otpExpired(): bool
    {
        return ! $this->otp_expires_at || $this->otp_expires_at->isPast();
    }
}
