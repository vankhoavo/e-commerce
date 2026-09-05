<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address', 'ip_version', 'ip_type', 'ipv6', 'organization', 'asn',
        'city', 'region', 'country', 'country_code', 'continent', 'device',
        'browser', 'operating_system', 'user_agent', 'ptr', 'is_proxy',
        'is_vpn', 'is_tor', 'is_mobile', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'ip_version' => 'integer',
            'is_proxy' => 'boolean',
            'is_vpn' => 'boolean',
            'is_tor' => 'boolean',
            'is_mobile' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
