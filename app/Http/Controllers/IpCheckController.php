<?php

namespace App\Http\Controllers;

use App\Models\IpAccessLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class IpCheckController extends Controller
{
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
            'currentIp' => $request->ip(),
        ]);
    }

    public function lookup(Request $request): Response
    {
        $data = $request->validate(['ip' => ['required', 'ip']]);
        $ip = $data['ip'];
        $ipwho = [];
        $ipinfo = [];
        $ptr = null;

        try {
            $ipwho = Http::timeout(4)->acceptJson()->get("https://ipwho.is/{$ip}")->json();
        } catch (\Throwable $e) {
            Log::debug('IP lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        try {
            $ipinfo = Http::timeout(4)->acceptJson()->get("https://ipinfo.io/{$ip}/json")->json();
        } catch (\Throwable $e) {
            Log::debug('IPinfo lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        return Inertia::render('admin/IpCheck', [
            'lookup' => [
                'ip' => $ip,
                'organization' => data_get($ipwho, 'connection.org') ?: data_get($ipinfo, 'org'),
                'asn' => data_get($ipwho, 'connection.asn') ? 'AS'.data_get($ipwho, 'connection.asn') : null,
                'city' => data_get($ipwho, 'city') ?: data_get($ipinfo, 'city'),
                'region' => data_get($ipwho, 'region') ?: data_get($ipinfo, 'region'),
                'country' => data_get($ipwho, 'country') ?: data_get($ipinfo, 'country'),
                'country_code' => data_get($ipwho, 'country_code') ?: data_get($ipinfo, 'country'),
                'continent' => data_get($ipwho, 'continent'),
                'type' => data_get($ipwho, 'type'),
                'hostname' => data_get($ipinfo, 'hostname'),
                'ptr' => $ptr,
                'security' => data_get($ipwho, 'security'),
            ],
        ]);
    }
}
