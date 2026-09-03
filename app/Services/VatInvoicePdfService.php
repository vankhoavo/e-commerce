<?php
namespace App\Services;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
class VatInvoicePdfService
{
    public function binary(Order$order,bool$vat=false):string{return Pdf::loadView('pdf.vat-invoice',['order'=>$order->load('items'),'vat'=>$vat])->setPaper('a4')->setOption('defaultFont','DejaVu Sans')->output();}
    public function filename(Order$order,bool$vat=false):string{return($vat?'hoa-don-VAT-':'hoa-don-').$order->code.'.pdf';}
}
