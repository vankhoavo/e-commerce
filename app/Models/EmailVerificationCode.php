<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class EmailVerificationCode extends Model
{
    protected $fillable = ['user_id', 'email', 'code', 'expires_at', 'attempts', 'verified_at'];

    protected $hidden = ['code'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expired(): bool
    {
        return Carbon::now()->greaterThan($this->expires_at);
    }
}
