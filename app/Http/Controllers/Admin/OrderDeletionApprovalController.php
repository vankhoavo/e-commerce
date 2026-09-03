<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminOrderDeletionRequest;
use App\Models\AdminProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderDeletionApprovalController
{
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

            foreach (DB::table('order_items')->whereIn('order_id', array_filter($orderIds))->get(['product_id', 'quantity']) as $item) {
                if (!$item->product_id) {
                    continue;
                }

                $product = AdminProduct::query()->lockForUpdate()->find($item->product_id);
                if (!$product) {
                    continue;
                }

                $product->increment('stock', (int) $item->quantity);
                $product->decrement('sold_count', min((int) $product->sold_count, (int) $item->quantity));
            }

            DB::table('order_items')->whereIn('order_id', array_filter($orderIds))->delete();
            DB::table('orders')->whereIn('id', array_filter($orderIds))->delete();
            $locked->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
        });

        return back()->with('success', 'Đã phê duyệt xóa đơn hàng và hoàn số lượng sản phẩm về kho.');
    }
}
