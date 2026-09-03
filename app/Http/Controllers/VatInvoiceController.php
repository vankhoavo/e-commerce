<?php
namespace App\Http\Controllers;
use App\Models\Order;
use App\Services\VatInvoicePdfService;
use Illuminate\Http\Response;
class VatInvoiceController extends Controller
{
    public function download(Order$order,VatInvoicePdfService$pdf):Response{return $this->stream($order,$pdf,false);}
    public function vat(Order$order,VatInvoicePdfService$pdf):Response{abort_unless((bool)$order->vat_invoice_requested,404);abort_unless($order->status==='Đã duyệt'||$order->status==='Đang giao'||$order->status==='Đã giao',422,'Hóa đơn VAT chỉ có sau khi đơn hàng được xác nhận.');return $this->stream($order,$pdf,true);}
    private function stream(Order$order,VatInvoicePdfService$pdf,bool$vat):Response{$recipient=request()->user();abort_unless($recipient->id===$order->user_id||$recipient->isBackOffice(),403);$name=$pdf->filename($order,$vat);return response($pdf->binary($order,$vat))->header('Content-Type','application/pdf')->header('Content-Disposition','inline; filename="'.$name.'"');}
}
