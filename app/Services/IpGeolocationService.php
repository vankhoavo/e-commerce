<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpGeolocationService
{
    public function lookup(string $ip): array
    {
        $ipwho = [];
        $ipinfo = [];

        try {
            $response = Http::timeout(4)->acceptJson()->get("https://ipwho.is/{$ip}");
            if ($response->successful()) {
                $ipwho = $response->json();
            }
        } catch (\Throwable $e) {
            Log::debug('IP lookup ipwho.is failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        try {
            $response = Http::timeout(4)->acceptJson()->get("https://ipinfo.io/{$ip}/json");
            if ($response->successful()) {
                $ipinfo = $response->json();
            }
        } catch (\Throwable $e) {
            Log::debug('IP lookup ipinfo failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        $security = data_get($ipwho, 'security', []);

        return [
            'organization' => data_get($ipwho, 'connection.org') ?: data_get($ipinfo, 'org'),
            'asn' => data_get($ipwho, 'connection.asn')
                ? 'AS'.data_get($ipwho, 'connection.asn')
                : $this->normalizeAsn(data_get($ipinfo, 'org')),
            'city' => data_get($ipwho, 'city') ?: data_get($ipinfo, 'city'),
            'region' => data_get($ipwho, 'region') ?: data_get($ipinfo, 'region'),
            'country' => data_get($ipwho, 'country') ?: $this->countryNameFromCode(data_get($ipinfo, 'country')),
            'country_code' => data_get($ipwho, 'country_code') ?: data_get($ipinfo, 'country'),
            'continent' => data_get($ipwho, 'continent'),
            'type' => data_get($ipwho, 'type'),
            'hostname' => data_get($ipinfo, 'hostname'),
            'security' => $security,
            'is_proxy' => data_get($security, 'proxy'),
            'is_vpn' => data_get($security, 'vpn'),
            'is_tor' => data_get($security, 'tor'),
            'is_mobile' => (bool) (data_get($ipwho, 'connection.type') === 'mobile' || data_get($ipwho, 'is_mobile')),
            'metadata' => [
                'ipinfo_hostname' => data_get($ipinfo, 'hostname'),
                'ipinfo_org' => data_get($ipinfo, 'org'),
                'ipinfo_loc' => data_get($ipinfo, 'loc'),
                'ipinfo_timezone' => data_get($ipinfo, 'timezone'),
                'sources' => ['IPinfo', 'ipwho.is'],
            ],
        ];
    }

    private function normalizeAsn(?string $organization): ?string
    {
        if (! $organization) {
            return null;
        }

        return preg_match('/\\bAS\\d+\\b/i', $organization, $matches)
            ? strtoupper($matches[0])
            : null;
    }

    private function countryNameFromCode(?string $code): ?string
    {
        return $code;
    }
}
