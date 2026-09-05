<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\VatInvoicePdfService;
use Illuminate\Http\Response;

class VatInvoiceController extends Controller
{
    public function download(Order $order, VatInvoicePdfService $pdf): Response
    {
        abort_unless((bool) $order->vat_invoice_requested, 404);
        abort_unless(in_array($order->status, ['Đã duyệt'], true), 422, 'Hóa đơn VAT hiện chỉ khả dụng khi đơn hàng đã được duyệt.');

        $recipient = request()->user();
        abort_unless($recipient->id === $order->user_id || $recipient->isAdmin(), 403);

        return response($pdf->binary($order))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$pdf->filename($order).'"');
    }
}
