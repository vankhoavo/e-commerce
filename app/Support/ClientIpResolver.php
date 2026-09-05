<?php

namespace App\Support;

use Illuminate\Http\Request;

class ClientIpResolver
{
    public function resolve(Request $request): ?string
    {
        $ip = $request->ip();

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }
}
