<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequest extends Model
{
    use HasFactory;

    protected $fillable = ['order_id','customer_id','reason','status','customer_note','sales_approved_by','sales_approved_at','admin_approved_by','admin_approved_at','received_by','received_at','inspection_note','refund_amount','refund_status','refunded_at'];

    protected function casts(): array
    {
        return ['sales_approved_at'=>'datetime','admin_approved_at'=>'datetime','received_at'=>'datetime','refunded_at'=>'datetime','refund_amount'=>'integer'];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
    public function salesApprover(): BelongsTo { return $this->belongsTo(User::class, 'sales_approved_by'); }
    public function adminApprover(): BelongsTo { return $this->belongsTo(User::class, 'admin_approved_by'); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
}
