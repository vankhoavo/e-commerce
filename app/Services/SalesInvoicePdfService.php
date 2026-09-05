<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class SalesInvoicePdfService
{
    public function binary(Order $order): string
    {
        return Pdf::loadView('pdf.sales-invoice', ['order' => $order->load('items')])
            ->setPaper('a4')
            ->setOption('defaultFont', 'DejaVu Serif')
            ->output();
    }

    public function filename(Order $order): string
    {
        return 'hoa-don-ban-hang-'.$order->code.'.pdf';
    }
}
