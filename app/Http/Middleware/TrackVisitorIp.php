<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\IpAccessLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitorIp
{
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
        $ip = $request->ip();
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return;
        }

        $version = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 6 : 4;
        $agent = (string) $request->userAgent();
        $parsed = $this->parseUserAgent($agent);
        $ipwho = [];
        $ipinfo = [];
        $ptr = null;

        try {
            $ipwho = Http::timeout(2)->acceptJson()->get("https://ipwho.is/{$ip}")->json();
        } catch (\Throwable $e) {
            Log::debug('IP lookup ipwho.is failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        try {
            $ipinfo = Http::timeout(2)->acceptJson()->get("https://ipinfo.io/{$ip}/json")->json();
        } catch (\Throwable $e) {
            Log::debug('IP lookup ipinfo failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        try {
            $dns = Http::timeout(2)->acceptJson()->get('https://dns.google/resolve', ['name' => $this->ptrName($ip), 'type' => 'PTR'])->json();
            $ptr = data_get($dns, 'Answer.0.data');
        } catch (\Throwable $e) {
            Log::debug('IP PTR lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        $country = data_get($ipwho, 'country') ?: data_get($ipinfo, 'country');
        $countryCode = data_get($ipwho, 'country_code') ?: data_get($ipinfo, 'country');
        $city = data_get($ipwho, 'city') ?: data_get($ipinfo, 'city');
        $region = data_get($ipwho, 'region') ?: data_get($ipinfo, 'region');
        $continent = data_get($ipwho, 'continent');
        $org = data_get($ipwho, 'connection.org') ?: data_get($ipinfo, 'org');
        $asn = data_get($ipwho, 'connection.asn');
        $ipType = data_get($ipwho, 'type') ?: ($version === 6 ? 'IPv6' : 'IPv4');
        $mobile = (bool) (data_get($ipwho, 'connection.type') === 'mobile' || data_get($ipwho, 'is_mobile') || $parsed['device'] === 'Phone');

        IpAccessLog::create([
            'user_id' => $userId,
            'ip_address' => $ip,
            'ip_version' => $version,
            'ip_type' => $ipType,
            'ipv6' => $version === 6 ? $ip : null,
            'organization' => $org,
            'asn' => $asn ? 'AS'.$asn : data_get($ipinfo, 'org'),
            'city' => $city,
            'region' => $region,
            'country' => $country,
            'country_code' => $countryCode,
            'continent' => $continent,
            'device' => $parsed['device'],
            'browser' => $parsed['browser'],
            'operating_system' => $parsed['os'],
            'user_agent' => $agent,
            'ptr' => $ptr,
            'is_proxy' => data_get($ipwho, 'security.proxy'),
            'is_vpn' => data_get($ipwho, 'security.vpn'),
            'is_tor' => data_get($ipwho, 'security.tor'),
            'is_mobile' => $mobile,
            'metadata' => [
                'ipinfo_hostname' => data_get($ipinfo, 'hostname'),
                'ipinfo_org' => data_get($ipinfo, 'org'),
                'ipinfo_loc' => data_get($ipinfo, 'loc'),
                'ipinfo_timezone' => data_get($ipinfo, 'timezone'),
                'sources' => ['IPinfo', 'ipwho.is', 'Google DNS PTR'],
            ],
        ]);
    }

    private function ptrName(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return implode('.', array_reverse(str_split(str_replace(':', '', inet_pton($ip) ? bin2hex(inet_pton($ip)) : ''), 1))) . '.ip6.arpa';
        }

        return implode('.', array_reverse(explode('.', $ip))) . '.in-addr.arpa';
    }

    private function parseUserAgent(string $agent): array
    {
        $device = preg_match('/iPhone|iPod/i', $agent) ? 'Phone' : (preg_match('/iPad/i', $agent) ? 'Tablet' : (preg_match('/Android/i', $agent) && preg_match('/Mobile/i', $agent) ? 'Phone' : (preg_match('/Android/i', $agent) ? 'Tablet' : (preg_match('/Windows|Macintosh|Linux/i', $agent) ? 'Desktop' : 'Unknown'))));
        $browser = preg_match('/Edg\//i', $agent) ? 'Microsoft Edge' : (preg_match('/Chrome\//i', $agent) ? 'Google Chrome' : (preg_match('/Firefox\//i', $agent) ? 'Mozilla Firefox' : (preg_match('/Safari\//i', $agent) && ! preg_match('/Chrome\//i', $agent) ? 'Safari' : (preg_match('/OPR\//i', $agent) ? 'Opera' : 'Unknown'))));
        $os = preg_match('/iPhone|iPad|iPod/i', $agent) ? 'iOS/iPadOS' : (preg_match('/Windows NT/i', $agent) ? 'Windows' : (preg_match('/Mac OS X/i', $agent) ? 'macOS' : (preg_match('/Android/i', $agent) ? 'Android' : (preg_match('/Linux/i', $agent) ? 'Linux' : 'Unknown'))));

        return compact('device', 'browser', 'os');
    }
}
