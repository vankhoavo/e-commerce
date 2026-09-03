<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReturnRequestController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Returns', [
            'requests' => ReturnRequest::query()
                ->with(['order:id,code,customer_name,total,status', 'customer:id,name,email', 'salesApprover:id,name', 'adminApprover:id,name', 'receiver:id,name'])
                ->latest()
                ->paginate(20)
                ->through(fn (ReturnRequest $r) => [
                    'id' => $r->id,
                    'status' => $r->status,
                    'reason' => $r->reason,
                    'customerNote' => $r->customer_note,
                    'inspectionNote' => $r->inspection_note,
                    'refundAmount' => (int) $r->refund_amount,
                    'refundStatus' => $r->refund_status,
                    'order' => $r->order?->only(['id','code','customer_name','total','status']),
                    'customer' => $r->customer?->only(['id','name','email']),
                    'salesApprover' => $r->salesApprover?->only(['id','name']),
                    'adminApprover' => $r->adminApprover?->only(['id','name']),
                    'receiver' => $r->receiver?->only(['id','name']),
                    'createdAt' => $r->created_at?->toIso8601String(),
                    'updatedAt' => $r->updated_at?->toIso8601String(),
                ]),
        ]);
    }

    public function salesApprove(Request $request, ReturnRequest $returnRequest): RedirectResponse
    {
        abort_unless(in_array($request->user()->role?->value, ['admin', 'sales'], true), 403, 'Chỉ Bán hàng hoặc Admin được tiếp nhận yêu cầu trả hàng.');
        abort_unless($returnRequest->status === 'customer_requested', 422, 'Yêu cầu không còn ở trạng thái chờ tiếp nhận.');

        $returnRequest->update([
            'status' => 'awaiting_admin',
            'sales_approved_by' => $request->user()->id,
            'sales_approved_at' => now(),
        ]);

        $returnRequest->order?->update(['status' => 'Chờ Admin duyệt trả hàng']);

        return back()->with('success', 'Đã tiếp nhận yêu cầu trả hàng và chuyển Admin xét duyệt.');
    }

    public function adminApprove(Request $request, ReturnRequest $returnRequest): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Chỉ Admin được phê duyệt yêu cầu trả hàng.');
        abort_unless($returnRequest->status === 'awaiting_admin', 422, 'Yêu cầu chưa qua bước Bán hàng.');

        $returnRequest->update([
            'status' => 'awaiting_receive',
            'admin_approved_by' => $request->user()->id,
            'admin_approved_at' => now(),
            'refund_amount' => (int) ($returnRequest->order?->total ?? 0),
        ]);

        $returnRequest->order?->update(['status' => 'Chờ nhận hàng hoàn']);

        return back()->with('success', 'Đã phê duyệt trả hàng. Chờ người bán nhận hàng hoàn để kiểm tra.');
    }

    public function receive(Request $request, ReturnRequest $returnRequest): RedirectResponse
    {
        abort_unless(in_array($request->user()->role?->value, ['admin', 'sales'], true), 403, 'Chỉ Bán hàng hoặc Admin được ghi nhận hàng hoàn.');
        abort_unless($returnRequest->status === 'awaiting_receive', 422, 'Yêu cầu chưa ở bước nhận hàng hoàn.');

        $returnRequest->update([
            'status' => 'inspecting',
            'received_by' => $request->user()->id,
            'received_at' => now(),
        ]);

        $returnRequest->order?->update(['status' => 'Đang kiểm tra hàng']);

        return back()->with('success', 'Đã nhận hàng hoàn. Đơn hàng đang được kiểm tra.');
    }

    public function refund(Request $request, ReturnRequest $returnRequest): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Chỉ Admin được quyết định kết quả hoàn tiền.');
        abort_unless($returnRequest->status === 'inspecting', 422, 'Đơn hàng chưa ở trạng thái đang kiểm tra hàng.');

        DB::transaction(function () use ($returnRequest): void {
            $returnRequest->update([
                'status' => 'refunded',
                'refund_status' => 'completed',
                'refunded_at' => now(),
            ]);
            $returnRequest->order?->update(['status' => 'Đã hoàn tiền']);
        });

        return back()->with('success', 'Đã phê duyệt hoàn tiền cho đơn hàng.');
    }

    public function reject(Request $request, ReturnRequest $returnRequest): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Chỉ Admin được từ chối yêu cầu trả hàng.');
        abort_unless(in_array($returnRequest->status, ['customer_requested', 'awaiting_admin', 'inspecting'], true), 422, 'Yêu cầu không thể từ chối ở trạng thái hiện tại.');

        $returnRequest->update([
            'status' => 'rejected',
            'refund_status' => 'rejected',
        ]);
        $returnRequest->order?->update(['status' => 'Đã giao']);

        return back()->with('success', 'Đã từ chối yêu cầu trả hàng. Đơn hàng được trả lại trạng thái đã giao.');
    }

    public function returnToCustomer(Request $request, ReturnRequest $returnRequest): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Chỉ Admin được xác nhận trả hàng lại khách.');
        abort_unless($returnRequest->status === 'inspecting', 422, 'Yêu cầu chưa ở bước kiểm tra hàng.');

        $returnRequest->update([
            'status' => 'rejected',
            'refund_status' => 'rejected',
            'refunded_at' => null,
        ]);
        $returnRequest->order?->update(['status' => 'Trả lại khách hàng']);

        return back()->with('success', 'Đã từ chối hoàn tiền và chuyển đơn sang trạng thái trả hàng lại cho khách.');
    }

    public function storeCustomer(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        abort_unless($order->status === 'Đã giao', 422, 'Chỉ đơn hàng đã giao mới có thể yêu cầu trả hàng.');
        abort_if(ReturnRequest::query()->where('order_id', $order->id)->whereNotIn('status', ['refunded', 'rejected'])->exists(), 422, 'Đơn hàng đã có yêu cầu trả hàng.');

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $return = ReturnRequest::create([
            'order_id' => $order->id,
            'customer_id' => $request->user()->id,
            'reason' => $data['reason'],
            'customer_note' => $data['customer_note'] ?? null,
            'status' => 'customer_requested',
            'refund_amount' => $order->total,
        ]);

        $order->update(['status' => 'Yêu cầu trả hàng']);

        return response()->json([
            'request_id' => $return->id,
            'order_id' => $order->id,
            'status' => $order->status,
        ]);
    }
}
