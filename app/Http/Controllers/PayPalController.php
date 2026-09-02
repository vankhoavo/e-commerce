<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayPalController extends Controller
{
    private function baseUrl(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function accessToken(): string
    {
        $response = Http::asForm()
            ->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.client_secret'))
            ->post($this->baseUrl().'/v1/oauth2/token', ['grant_type' => 'client_credentials'])
            ->throw();

        return (string) $response->json('access_token');
    }

    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount_vnd' => ['required', 'numeric', 'min:1000', 'max:1000000000'],
        ]);

        $rate = (float) config('services.paypal.vnd_to_usd', 25000);
        $amountUsd = round(((float) $validated['amount_vnd']) / $rate, 2);

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($this->baseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => config('services.paypal.currency', 'USD'),
                        'value' => number_format($amountUsd, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'brand_name' => 'TechStore',
                    'user_action' => 'PAY_NOW',
                ],
            ])
            ->throw();

        return response()->json([
            'id' => $response->json('id'),
            'amount_usd' => $amountUsd,
        ]);
    }

    public function captureOrder(Request $request, string $orderId): JsonResponse
    {
        abort_unless(preg_match('/^[A-Z0-9-]+$/i', $orderId), 422, 'Mã PayPal không hợp lệ.');

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($this->baseUrl().'/v2/checkout/orders/'.$orderId.'/capture');

        if ($response->failed()) {
            return response()->json(['message' => 'PayPal không thể hoàn tất thanh toán.'], 422);
        }

        return response()->json([
            'status' => $response->json('status'),
            'id' => $response->json('id'),
        ]);
    }
}
