<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

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

    private function paypalErrorMessage($response, string $fallback): string
    {
        $name = (string) $response->json('name', '');
        $message = (string) $response->json('message', '');
        $details = $response->json('details', []);
        $issue = is_array($details) ? (string) data_get($details, '0.issue', '') : '';
        $description = is_array($details) ? (string) data_get($details, '0.description', '') : '';

        $parts = array_values(array_filter([$name, $issue, $message, $description]));
        return $parts ? implode(' — ', array_unique($parts)) : $fallback;
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

        try {
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
                ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Không thể kết nối PayPal Sandbox từ máy chủ TechStore.'], 502);
        }

        if ($response->failed() || ! $response->json('id')) {
            return response()->json([
                'message' => $this->paypalErrorMessage($response, 'PayPal Sandbox không thể tạo đơn hàng.'),
            ], 422);
        }

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

        try {
            $response = Http::withToken($this->accessToken())
                ->acceptJson()
                ->withHeaders(['PayPal-Request-Id' => (string) Str::uuid()])
                ->post($this->baseUrl().'/v2/checkout/orders/'.$orderId.'/capture');
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Không thể kết nối PayPal Sandbox từ máy chủ TechStore.'], 502);
        }

        if ($response->failed()) {
            return response()->json([
                'message' => $this->paypalErrorMessage($response, 'PayPal Sandbox không thể hoàn tất thanh toán.'),
                'paypal_status' => $response->status(),
            ], 422);
        }

        return response()->json([
            'status' => $response->json('status'),
            'id' => $response->json('id'),
            'environment' => 'sandbox',
        ]);
    }
}
