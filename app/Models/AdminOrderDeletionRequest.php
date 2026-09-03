<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminOrderDeletionRequest extends Model
{
    protected $table = 'admin_order_deletion_requests';

    protected $fillable = [
        'requested_by',
        'order_id',
        'request_type',
        'status',
        'approved_by',
        'approved_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
