<?php

namespace App\Http\Controllers;

use App\Models\IpAccessLog;
use App\Services\IpGeolocationService;
use App\Support\ClientIpResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IpCheckController extends Controller
{
    public function __construct(
        private readonly ClientIpResolver $clientIpResolver,
        private readonly IpGeolocationService $ipGeolocation,
    ) {
    }

    public function index(Request $request): Response
    {
        $base = IpAccessLog::query()->whereNotNull('user_id');
        $logs = (clone $base)->latest()->paginate(20)->withQueryString();

        $countries = (clone $base)
            ->selectRaw('country, country_code, COUNT(*) as visits')
            ->whereNotNull('country')
            ->groupBy('country', 'country_code')
            ->orderByDesc('visits')
            ->limit(5)
            ->get()
            ->map(fn (IpAccessLog $item, int $index): array => [
                'rank' => $index + 1,
                'country' => $item->country,
                'code' => $item->country_code,
                'visits' => (int) $item->visits,
            ]);

        return Inertia::render('admin/IpCheck', [
            'logs' => $logs->through(fn (IpAccessLog $log): array => [
                'id' => $log->id,
                'created_at' => $log->created_at?->toISOString(),
                'ipv4' => $log->ip_version === 4 ? $log->ip_address : null,
                'ipv6' => $log->ip_version === 6 ? $log->ip_address : null,
                'ip_type' => $log->ip_type,
                'organization' => $log->organization,
                'asn' => $log->asn,
                'city' => $log->city,
                'region' => $log->region,
                'country' => $log->country,
                'country_code' => $log->country_code,
                'continent' => $log->continent,
                'device' => $log->device,
                'browser' => $log->browser,
                'os' => $log->operating_system,
                'ptr' => $log->ptr,
                'is_proxy' => $log->is_proxy,
                'is_vpn' => $log->is_vpn,
                'is_tor' => $log->is_tor,
                'metadata' => $log->metadata,
            ]),
            'countries' => $countries,
            'summary' => [
                'total' => (clone $base)->count(),
                'ipv4' => (clone $base)->where('ip_version', 4)->count(),
                'ipv6' => (clone $base)->where('ip_version', 6)->count(),
                'devices' => (clone $base)->whereNotNull('device')->where('device', '!=', 'Unknown')->distinct()->count('device'),
            ],
            'currentIp' => $this->clientIpResolver->resolve($request),
        ]);
    }

    public function lookup(Request $request): Response
    {
        $data = $request->validate(['ip' => ['required', 'ip']]);
        $ip = $data['ip'];
        $geo = $this->ipGeolocation->lookup($ip);

        return Inertia::render('admin/IpCheck', [
            'lookup' => [
                'ip' => $ip,
                'organization' => $geo['organization'],
                'asn' => $geo['asn'],
                'city' => $geo['city'],
                'region' => $geo['region'],
                'country' => $geo['country'],
                'country_code' => $geo['country_code'],
                'continent' => $geo['continent'],
                'type' => $geo['type'],
                'hostname' => $geo['hostname'],
                'ptr' => $geo['hostname'],
                'security' => $geo['security'],
            ],
        ]);
    }
}
