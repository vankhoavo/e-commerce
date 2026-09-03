<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;
    protected $fillable=['user_id','code','status','customer_name','customer_phone','customer_email','customer_address','note','payment','coupon_code','discount','vat_invoice_requested','vat_company_name','vat_tax_code','vat_address','vat_email','vat_rate','vat_amount','paypal_order_id','subtotal','shipping','total_shipping','total','cancelled_at','returned_at'];
    protected function casts(): array{return ['vat_invoice_requested'=>'boolean','vat_rate'=>'decimal:2','vat_amount'=>'integer','discount'=>'integer','cancelled_at'=>'datetime','returned_at'=>'datetime'];}
    public function user():BelongsTo{return $this->belongsTo(User::class);}
    public function items():HasMany{return $this->hasMany(OrderItem::class);}
}
