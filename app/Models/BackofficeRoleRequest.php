<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackofficeRoleRequest extends Model
{
    use HasFactory;
    protected $fillable=['requested_by','role','name','email','phone','password_hash','status','approved_by','approved_at','rejected_at','reason'];
    protected function casts(): array { return ['approved_at'=>'datetime','rejected_at'=>'datetime']; }
    public function requester(): BelongsTo { return $this->belongsTo(User::class,'requested_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class,'approved_by'); }
}
