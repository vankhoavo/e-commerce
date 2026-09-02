<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PayPalController extends Controller
{
    private function baseUrl(): string
    {
        // TechStore is intentionally locked to PayPal Sandbox for testing.
        return 'https://api-m.sandbox.paypal.com';
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
        abort_if(! config('services.paypal.client_id') || ! config('services.paypal.client_secret'), 503, 'PayPal Sandbox chưa được cấu hình.');

        $validated = $request->validate([
            'amount_vnd' => ['required', 'numeric', 'min:25000', 'max:1000000000'],
        ]);

        $rate = max(1, (float) config('services.paypal.vnd_to_usd', 25000));
        $amountUsd = round(((float) $validated['amount_vnd']) / $rate, 2);

        abort_if($amountUsd < 1, 422, 'Số tiền thanh toán PayPal Sandbox phải từ 1 USD.');

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->withHeaders(['PayPal-Request-Id' => (string) Str::uuid()])
            ->post($this->baseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => 'TECHSTORE-'.now()->format('YmdHis'),
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($amountUsd, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'brand_name' => 'TechStore Sandbox',
                    'user_action' => 'PAY_NOW',
                    'shipping_preference' => 'NO_SHIPPING',
                ],
            ])
            ->throw();

        return response()->json([
            'id' => $response->json('id'),
            'amount_usd' => $amountUsd,
            'currency' => 'USD',
            'environment' => 'sandbox',
        ]);
    }

    public function captureOrder(Request $request, string $orderId): JsonResponse
    {
        abort_unless(preg_match('/^[A-Z0-9-]+$/i', $orderId), 422, 'Mã PayPal không hợp lệ.');

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->withHeaders(['PayPal-Request-Id' => (string) Str::uuid()])
            ->post($this->baseUrl().'/v2/checkout/orders/'.$orderId.'/capture');

        if ($response->failed()) {
            return response()->json(['message' => 'PayPal Sandbox không thể hoàn tất thanh toán.'], 422);
        }

        return response()->json([
            'status' => $response->json('status'),
            'id' => $response->json('id'),
            'environment' => 'sandbox',
        ]);
    }
}
