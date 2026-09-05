<?php

namespace App\Http\Controllers;

use App\Jobs\SendOrderReceivedEmailJob;
use App\Models\AdminProduct;
use App\Models\Order;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    private const COD_SHIPPING_FEE = 30000;
    private const VAT_RATE = 10.00;

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()->where('user_id', $request->user()->id)->with('items')->latest('created_at')->get()->map(fn (Order $order): array => $this->transform($order))->values();
        return response()->json(['orders' => $orders]);
    }

    public function store(Request $request, DatabaseManager $db): JsonResponse
    {
        $data = $request->validate([
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.phone' => ['required', 'string', 'max:30'],
            'customer.email' => ['nullable', 'email', 'max:255'],
            'customer.address' => ['required', 'string', 'max:500'],
            'customer.note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'integer', 'min:1'],
            'items.*.name' => ['nullable', 'string', 'max:500'],
            'items.*.image' => ['nullable', 'string', 'max:2000'],
            'items.*.price' => ['nullable', 'integer', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'subtotal' => ['required', 'integer', 'min:0'],
            'shipping' => ['required', 'integer', 'min:0'],
            'total_shipping' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:0'],
            'payment' => ['required', 'string', 'in:cod,paypal-sandbox,paypal-demo'],
            'paypal_order_id' => ['nullable', 'string', 'max:64'],
            'vat_invoice.requested' => ['nullable', 'boolean'],
            'vat_invoice.company_name' => ['nullable', 'string', 'max:255'],
            'vat_invoice.tax_code' => ['nullable', 'string', 'max:32'],
            'vat_invoice.address' => ['nullable', 'string', 'max:500'],
            'vat_invoice.email' => ['nullable', 'email', 'max:255'],
        ]);

        $vatRequested = (bool) data_get($data, 'vat_invoice.requested', false);
        $vatInvoice = (array) data_get($data, 'vat_invoice', []);
        if ($vatRequested) {
            $missing = collect([
                'company_name' => 'Vui lòng nhập tên công ty/đơn vị để xuất hóa đơn VAT.',
                'tax_code' => 'Vui lòng nhập mã số thuế để xuất hóa đơn VAT.',
                'address' => 'Vui lòng nhập địa chỉ xuất hóa đơn VAT.',
                'email' => 'Vui lòng nhập Email nhận hóa đơn VAT.',
            ])->first(fn (string $message, string $field): bool => ! filled($vatInvoice[$field] ?? null));
            if ($missing) return response()->json(['message' => $missing], 422);
        }

        if ($data['payment'] === 'paypal-sandbox') {
            if (blank($data['paypal_order_id'] ?? null)) {
                return response()->json(['message' => 'Thiếu mã đơn hàng PayPal Sandbox.'], 422);
            }
            if (Order::query()->where('paypal_order_id', $data['paypal_order_id'])->exists()) {
                return response()->json(['message' => 'Mã giao dịch PayPal này đã được sử dụng cho một đơn hàng khác.'], 422);
            }
        }

        $ids = collect($data['items'])->pluck('id')->unique()->values();
        $products = AdminProduct::query()->whereIn('id', $ids)->where('is_active', true)->get()->keyBy('id');
        if ($products->count() !== $ids->count()) return response()->json(['message' => 'Một hoặc nhiều sản phẩm không còn kinh doanh.'], 422);

        $calculatedSubtotal = 0;
        foreach ($data['items'] as $item) {
            $product = $products->get($item['id']);
            if (! $product) return response()->json(['message' => 'Sản phẩm không tồn tại.'], 422);
            if ($product->stock < (int) $item['quantity']) return response()->json(['message' => 'Tồn kho không đủ cho sản phẩm: '.$product->name], 422);
            $calculatedSubtotal += (int) $product->price * (int) $item['quantity'];
        }

        $shipping = (int) $data['shipping'];
        $totalShipping = (int) $data['total_shipping'];
        $expectedShipping = $data['payment'] === 'cod' ? self::COD_SHIPPING_FEE : 0;
        $calculatedTotal = $calculatedSubtotal + $expectedShipping;
        if ($calculatedSubtotal !== (int) $data['subtotal'] || $shipping !== $expectedShipping || $totalShipping !== $expectedShipping || $calculatedTotal !== (int) $data['total']) {
            return response()->json(['message' => 'Dữ liệu đơn hàng không hợp lệ hoặc giá sản phẩm đã thay đổi.'], 422);
        }

        if ($data['payment'] === 'paypal-sandbox') {
            try {
                $paypal = $this->paypalOrder($data['paypal_order_id']);
                $this->assertPaypalOrderMatchesTotal($paypal, $calculatedTotal);
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::warning('PayPal order verification failed.', ['paypal_order_id' => $data['paypal_order_id'], 'error' => $exception->getMessage()]);
                return response()->json(['message' => $exception->getMessage() ?: 'Không thể xác minh giao dịch PayPal Sandbox.'], 422);
            }
        }

        $vatAmount = $vatRequested ? (int) round(((int) $data['subtotal'] * self::VAT_RATE) / (100 + self::VAT_RATE)) : 0;
        $order = $db->transaction(function () use ($request, $data, $vatRequested, $vatInvoice, $vatAmount, $products): Order {
            do { $code = 'TS'.now()->format('ymdHis').str()->upper(Str::random(3)); } while (Order::query()->where('code', $code)->exists());
            $order = Order::query()->create([
                'user_id' => $request->user()->id,
                'code' => $code,
                'status' => 'Chờ xử lý',
                'customer_name' => $data['customer']['name'],
                'customer_phone' => $data['customer']['phone'],
                'customer_email' => $data['customer']['email'] ?? null,
                'customer_address' => $data['customer']['address'],
                'note' => $data['customer']['note'] ?? null,
                'payment' => $data['payment'],
                'vat_invoice_requested' => $vatRequested,
                'vat_company_name' => $vatRequested ? $vatInvoice['company_name'] : null,
                'vat_tax_code' => $vatRequested ? $vatInvoice['tax_code'] : null,
                'vat_address' => $vatRequested ? $vatInvoice['address'] : null,
                'vat_email' => $vatRequested ? $vatInvoice['email'] : null,
                'vat_rate' => $vatRequested ? self::VAT_RATE : 0,
                'vat_amount' => $vatAmount,
                'paypal_order_id' => $data['paypal_order_id'] ?? null,
                'subtotal' => $data['subtotal'],
                'shipping' => $data['shipping'],
                'total_shipping' => $data['total_shipping'],
                'total' => $data['total'],
            ]);
            foreach ($data['items'] as $item) {
                $product = $products->get($item['id']);
                $order->items()->create([
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->image,
                    'price' => (int) $product->price,
                    'quantity' => $item['quantity'],
                    'total' => (int) $product->price * (int) $item['quantity'],
                ]);
            }
            return $order->load('items');
        });

        $recipient = $order->customer_email ?: $request->user()->email;
        if ($order->payment === 'cod') {
            SendOrderReceivedEmailJob::dispatch($order->id, $recipient);
        } else {
            dispatch(function () use ($order, $recipient): void {
                try {
                    \Illuminate\Support\Facades\Mail::to($recipient)->send(new \App\Mail\OrderReceivedMail($order));
                } catch (\Throwable $exception) {
                    \Illuminate\Support\Facades\Log::warning('Không thể gửi Email xác nhận đơn hàng.', ['order_id' => $order->id, 'recipient' => $recipient, 'error' => $exception->getMessage()]);
                }
            })->afterResponse();
        }

        return response()->json(['order' => $this->transform($order)], 201);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        abort_unless($order->status === 'Chờ xử lý', 422, 'Đơn hàng không còn ở trạng thái có thể hủy.');
        $order->forceFill(['status' => 'Hủy hàng', 'cancelled_at' => now()])->save();
        return response()->json(['order' => $this->transform($order->load('items'))]);
    }

    public function returnOrder(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        abort_unless($order->status === 'Đã giao', 422, 'Chỉ đơn hàng đã giao mới có thể yêu cầu trả hàng.');
        $order->forceFill(['status' => 'Trả hàng', 'returned_at' => now()])->save();
        return response()->json(['order' => $this->transform($order->load('items'))]);
    }

    private function paypalOrder(string $orderId): array
    {
        $clientId = config('services.paypal.client_id');
        $clientSecret = config('services.paypal.client_secret');
        abort_if(blank($clientId) || blank($clientSecret), 503, 'PayPal Sandbox chưa được cấu hình.');

        $tokenResponse = Http::asForm()->withBasicAuth($clientId, $clientSecret)->timeout(15)->post('https://api-m.sandbox.paypal.com/v1/oauth2/token', ['grant_type' => 'client_credentials']);
        if ($tokenResponse->failed()) throw new \RuntimeException('Không thể xác thực với PayPal Sandbox.');
        $token = (string) $tokenResponse->json('access_token');
        if ($token === '') throw new \RuntimeException('PayPal Sandbox không trả về mã xác thực.');

        $response = Http::withToken($token)->acceptJson()->timeout(15)->get('https://api-m.sandbox.paypal.com/v2/checkout/orders/'.$orderId);
        if ($response->failed()) throw new \RuntimeException($this->paypalErrorMessage($response, 'Không thể xác minh đơn hàng PayPal Sandbox.'));
        return (array) $response->json();
    }

    private function assertPaypalOrderMatchesTotal(array $paypalOrder, int $expectedVnd): void
    {
        if (($paypalOrder['status'] ?? null) !== 'COMPLETED') throw new \RuntimeException('PayPal Sandbox chưa xác nhận thanh toán hoàn tất.');
        if (($paypalOrder['intent'] ?? null) !== 'CAPTURE') throw new \RuntimeException('Giao dịch PayPal không sử dụng hình thức CAPTURE hợp lệ.');
        $currency = (string) data_get($paypalOrder, 'purchase_units.0.amount.currency_code', '');
        $value = (string) data_get($paypalOrder, 'purchase_units.0.amount.value', '');
        $rate = max(1, (float) config('services.paypal.vnd_to_usd', 25000));
        $expectedUsd = number_format(round($expectedVnd / $rate, 2), 2, '.', '');
        if ($currency !== 'USD' || $value !== $expectedUsd) throw new \RuntimeException('Số tiền giao dịch PayPal không khớp với đơn hàng TechStore.');
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

    private function transform(Order $order): array
    {
        return [
            'id' => $order->id, 'code' => $order->code, 'createdAt' => optional($order->created_at)->toISOString(),
            'customer' => ['name' => $order->customer_name, 'phone' => $order->customer_phone, 'email' => $order->customer_email, 'address' => $order->customer_address, 'note' => $order->note],
            'vatInvoice' => ['requested' => (bool) $order->vat_invoice_requested, 'companyName' => $order->vat_company_name, 'taxCode' => $order->vat_tax_code, 'address' => $order->vat_address, 'email' => $order->vat_email, 'rate' => (float) $order->vat_rate, 'amount' => $order->vat_amount],
            'items' => $order->items->map(fn ($item): array => ['id' => $item->product_id ?? $item->id, 'name' => $item->name, 'price' => $item->price, 'image' => $item->image, 'quantity' => $item->quantity])->values()->all(),
            'subtotal' => $order->subtotal, 'shipping' => $order->shipping, 'totalShipping' => $order->total_shipping, 'total' => $order->total,
            'payment' => $order->payment, 'paypalOrderId' => $order->paypal_order_id, 'status' => $order->status,
            'cancelledAt' => optional($order->cancelled_at)->toISOString(), 'returnedAt' => optional($order->returned_at)->toISOString(),
        ];
    }
}
