<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\IpAccessLog;
use App\Services\IpGeolocationService;
use App\Support\ClientIpResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitorIp
{
    public function __construct(
        private readonly ClientIpResolver $clientIpResolver,
        private readonly IpGeolocationService $ipGeolocation,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $user = $request->user();

        if (
            $request->isMethod('GET')
            && $request->route()
            && ! $request->expectsJson()
            && ! $request->is('admin*')
            && $user?->role === UserRole::CUSTOMER
            && ! $request->session()->has('client_ip_logged')
        ) {
            $this->record($request, $user->id);
            $request->session()->put('client_ip_logged', true);
        }

        return $response;
    }

    private function record(Request $request, int $userId): void
    {
        $ip = $this->clientIpResolver->resolve($request);

        if (! $ip) {
            return;
        }

        $version = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 6 : 4;
        $agent = (string) $request->userAgent();
        $parsed = $this->parseUserAgent($agent);

        // Keep the visitor request fast. IP enrichment is best effort and
        // runs here only when an IP record is first created for the session.
        $geo = $this->ipGeolocation->lookup($ip);

        $ipType = $geo['type'] ?: ($version === 6 ? 'IPv6' : 'IPv4');
        $mobile = (bool) ($geo['is_mobile'] || $parsed['device'] === 'Phone');

        IpAccessLog::create([
            'user_id' => $userId,
            'ip_address' => $ip,
            'ip_version' => $version,
            'ip_type' => $ipType,
            'ipv6' => $version === 6 ? $ip : null,
            'organization' => $geo['organization'],
            'asn' => $geo['asn'],
            'city' => $geo['city'],
            'region' => $geo['region'],
            'country' => $geo['country'],
            'country_code' => $geo['country_code'],
            'continent' => $geo['continent'],
            'device' => $parsed['device'],
            'browser' => $parsed['browser'],
            'operating_system' => $parsed['os'],
            'user_agent' => $agent,
            'ptr' => $geo['hostname'],
            'is_proxy' => $geo['is_proxy'],
            'is_vpn' => $geo['is_vpn'],
            'is_tor' => $geo['is_tor'],
            'is_mobile' => $mobile,
            'metadata' => $geo['metadata'],
        ]);
    }

    private function parseUserAgent(string $agent): array
    {
        $device = preg_match('/iPhone|iPod/i', $agent) ? 'Phone' : (preg_match('/iPad/i', $agent) ? 'Tablet' : (preg_match('/Android/i', $agent) && preg_match('/Mobile/i', $agent) ? 'Phone' : (preg_match('/Android/i', $agent) ? 'Tablet' : (preg_match('/Windows|Macintosh|Linux/i', $agent) ? 'Desktop' : 'Unknown'))));
        $browser = preg_match('/Edg\//i', $agent) ? 'Microsoft Edge' : (preg_match('/Chrome\//i', $agent) ? 'Google Chrome' : (preg_match('/Firefox\//i', $agent) ? 'Mozilla Firefox' : (preg_match('/Safari\//i', $agent) && ! preg_match('/Chrome\//i', $agent) ? 'Safari' : (preg_match('/OPR\//i', $agent) ? 'Opera' : 'Unknown'))));
        $os = preg_match('/iPhone|iPad|iPod/i', $agent) ? 'iOS/iPadOS' : (preg_match('/Windows NT/i', $agent) ? 'Windows' : (preg_match('/Mac OS X/i', $agent) ? 'macOS' : (preg_match('/Android/i', $agent) ? 'Android' : (preg_match('/Linux/i', $agent) ? 'Linux' : 'Unknown'))));

        return compact('device', 'browser', 'os');
    }
}
