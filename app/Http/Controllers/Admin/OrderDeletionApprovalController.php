<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminOrderDeletionRequest;
use App\Models\AdminProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderDeletionApprovalController
{
    /**
     * These are the order states where inventory has already been deducted.
     * Pending orders must not add stock back because their stock was never deducted.
     */
    private const STOCK_DEDUCTED_STATUSES = [
        'Đã duyệt',
        'Đang giao',
        'Đã giao',
        'Hoàn tất',
        'Yêu cầu trả hàng',
        'Chờ Admin duyệt trả hàng',
        'Chờ nhận hàng hoàn',
        'Đang kiểm tra hàng',
    ];

    public function approve(Request $request, AdminOrderDeletionRequest $deletionRequest): RedirectResponse
    {
        abort_unless($deletionRequest->status === 'pending', 422, 'Yêu cầu này đã được xử lý.');
        abort_if($deletionRequest->requested_by === $request->user()->id, 403, 'Tài khoản tạo yêu cầu không được tự phê duyệt yêu cầu của mình.');

        DB::transaction(function () use ($deletionRequest, $request): void {
            $locked = AdminOrderDeletionRequest::query()->lockForUpdate()->findOrFail($deletionRequest->id);
            abort_unless($locked->status === 'pending', 422, 'Yêu cầu này đã được xử lý.');

            $orderIds = $locked->request_type === 'all'
                ? DB::table('orders')->pluck('id')->all()
                : [$locked->order_id];

            abort_if($locked->request_type === 'single' && $locked->order_id === null, 422, 'Đơn hàng không còn tồn tại.');

            $orderIds = array_values(array_filter($orderIds));
            $orderStatuses = DB::table('orders')->whereIn('id', $orderIds)->pluck('status', 'id');

            foreach (DB::table('order_items')->whereIn('order_id', $orderIds)->get(['order_id', 'product_id', 'quantity']) as $item) {
                if (!$item->product_id || !in_array($orderStatuses[$item->order_id] ?? null, self::STOCK_DEDUCTED_STATUSES, true)) {
                    continue;
                }

                $product = AdminProduct::query()->lockForUpdate()->find($item->product_id);
                if (!$product) {
                    continue;
                }

                $quantity = (int) $item->quantity;
                $product->increment('stock', $quantity);
                $product->decrement('sold_count', min((int) $product->sold_count, $quantity));
            }

            // return_requests has cascadeOnDelete for orders, so deleting the order
            // also removes its return history. order_items also cascades, but deleting
            // explicitly keeps the operation deterministic and avoids FK surprises.
            DB::table('order_items')->whereIn('order_id', $orderIds)->delete();
            DB::table('orders')->whereIn('id', $orderIds)->delete();

            $locked->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);
        });

        return back()->with('success', 'Đã phê duyệt xóa đơn hàng và hoàn số lượng sản phẩm về kho.');
    }
}
