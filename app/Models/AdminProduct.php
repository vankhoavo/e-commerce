<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AdminProduct extends Model{use HasFactory;protected $fillable=['category_id','product_type','product_line','name','slug','sku','brand','badge','rating','sold_count','price','old_price','stock','image','gallery','short_description','description','specs','source','market_source','is_active'];protected function casts():array{return['price'=>'integer','old_price'=>'integer','rating'=>'decimal:1','sold_count'=>'integer','stock'=>'integer','gallery'=>'array','specs'=>'array','is_active'=>'boolean'];}public function category():BelongsTo{return $this->belongsTo(ProductCategory::class,'category_id');}}
