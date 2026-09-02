<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with('items')
            ->latest('created_at')
            ->get()
            ->map(fn (Order $order): array => $this->transform($order))
            ->values();

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
            'items.*.name' => ['required', 'string', 'max:500'],
            'items.*.image' => ['nullable', 'string', 'max:2000'],
            'items.*.price' => ['required', 'integer', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'subtotal' => ['required', 'integer', 'min:0'],
            'shipping' => ['required', 'integer', 'min:0'],
            'total_shipping' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:0'],
            'payment' => ['required', 'string', 'in:cod,paypal-sandbox,paypal-demo'],
            'paypal_order_id' => ['nullable', 'string', 'max:64'],
        ]);

        $calculatedSubtotal = collect($data['items'])->sum(fn (array $item): int => (int) $item['price'] * (int) $item['quantity']);
        $shipping = (int) $data['shipping'];
        $totalShipping = (int) $data['total_shipping'];
        $calculatedTotal = $calculatedSubtotal + $totalShipping;

        if ($calculatedSubtotal !== (int) $data['subtotal'] || $shipping !== $totalShipping || $calculatedTotal !== (int) $data['total']) {
            return response()->json(['message' => 'Dữ liệu đơn hàng không hợp lệ.'], 422);
        }

        $order = $db->transaction(function () use ($request, $data): Order {
            do {
                $code = 'TS'.now()->format('ymdHis').str()->upper(Str::random(3));
            } while (Order::query()->where('code', $code)->exists());

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
                'paypal_order_id' => $data['paypal_order_id'] ?? null,
                'subtotal' => $data['subtotal'],
                'shipping' => $data['shipping'],
                'total_shipping' => $data['total_shipping'],
                'total' => $data['total'],
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['id'],
                    'name' => $item['name'],
                    'image' => $item['image'] ?? null,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'total' => $item['price'] * $item['quantity'],
                ]);
            }

            return $order->load('items');
        });

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

    private function transform(Order $order): array
    {
        return [
            'id' => $order->id,
            'code' => $order->code,
            'createdAt' => optional($order->created_at)->toISOString(),
            'customer' => [
                'name' => $order->customer_name,
                'phone' => $order->customer_phone,
                'email' => $order->customer_email,
                'address' => $order->customer_address,
                'note' => $order->note,
            ],
            'items' => $order->items->map(fn ($item): array => [
                'id' => $item->product_id ?? $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'image' => $item->image,
                'quantity' => $item->quantity,
            ])->values()->all(),
            'subtotal' => $order->subtotal,
            'shipping' => $order->shipping,
            'totalShipping' => $order->total_shipping,
            'total' => $order->total,
            'payment' => $order->payment,
            'paypalOrderId' => $order->paypal_order_id,
            'status' => $order->status,
            'cancelledAt' => optional($order->cancelled_at)->toISOString(),
            'returnedAt' => optional($order->returned_at)->toISOString(),
        ];
    }
}
