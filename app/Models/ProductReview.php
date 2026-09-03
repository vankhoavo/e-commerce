<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProductReview extends Model{use HasFactory;protected $fillable=['product_id','user_id','order_id','rating','content','is_approved'];protected function casts():array{return['rating'=>'integer','is_approved'=>'boolean'];}public function product():BelongsTo{return $this->belongsTo(AdminProduct::class,'product_id');}public function user():BelongsTo{return $this->belongsTo(User::class);}public function order():BelongsTo{return $this->belongsTo(Order::class);}}
