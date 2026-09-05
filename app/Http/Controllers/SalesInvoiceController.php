<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\SalesInvoicePdfService;
use Illuminate\Http\Response;

class SalesInvoiceController extends Controller
{
    public function download(Order $order, SalesInvoicePdfService $pdf): Response
    {
        abort_unless(in_array($order->status, ['Chờ xử lý', 'Đã duyệt'], true), 422, 'Hóa đơn bán hàng hiện chỉ khả dụng ở trạng thái Chờ xử lý hoặc Đã duyệt.');

        $recipient = request()->user();
        abort_unless($recipient->id === $order->user_id || $recipient->isAdmin(), 403);

        return response($pdf->binary($order))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$pdf->filename($order).'"');
    }
}
