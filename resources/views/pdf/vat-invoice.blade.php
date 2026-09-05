<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<style>
@page { margin: 9mm 10mm 16mm; }
* { box-sizing: border-box; }
body { font-family: "DejaVu Serif", "Times New Roman", serif; font-size: 9px; color: #172033; line-height: 1.3; }
.page { border: 1px solid #9fb3c8; padding: 7mm 7mm 24mm; min-height: 270mm; position: relative; }
.header { border-bottom: 2px solid #315ee8; padding-bottom: 8px; }
.header-table { width: 100%; border-collapse: collapse; }
.header-table td { vertical-align: middle; }
.title-wrap { width: 100%; text-align: center; }
.title-inline { display: inline-table; border-collapse: collapse; }
.logo-cell { width: 30mm; padding-right: 5mm; vertical-align: middle !important; }
.logo { width: 18mm; height: 18mm; display: block; }
.title-cell { text-align: left; vertical-align: middle !important; }
.company { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #24324a; margin-bottom: 3px; }
h1 { margin: 0; font-size: 17px; line-height: 1.15; font-weight: bold; text-transform: uppercase; color: #111827; white-space: nowrap; }
.subtitle { margin-top: 3px; font-size: 8px; font-style: italic; color: #5e6b7e; }
.invoice-ident { width: 100%; margin-top: 6px; border-collapse: collapse; }
.invoice-ident td { padding: 2px 4px; }
.invoice-ident .left { width: 50%; }
.invoice-ident .right { width: 50%; text-align: right; }
.section-title { margin: 7px 0 4px; padding: 3px 6px; background: #edf3ff; border-left: 3px solid #315ee8; font-weight: bold; font-size: 9px; color: #24324a; }
.seller, .buyer { width: 100%; border-collapse: collapse; }
.seller td, .buyer td { padding: 2px 4px; vertical-align: top; }
.seller .label, .buyer .label { font-weight: bold; white-space: nowrap; }
.seller .label { width: 17%; }
.seller .value { width: 33%; }
.buyer .label { width: 18%; }
.buyer .value { width: 32%; }
.payment-box { margin-top: 3px; padding: 4px 6px; border: 1px solid #b9c7d8; background: #f8fafc; }
.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
.items th { border: 1px solid #52677c; background: #eef3f8; padding: 5px 3px; text-align: center; vertical-align: middle; font-weight: bold; }
.items td { border: 1px solid #92a4b7; padding: 5px 3px; vertical-align: top; }
.items .index { width: 6%; }.items .description { width: 41%; }.items .unit { width: 10%; }.items .qty { width: 10%; }.items .price { width: 16%; }.items .amount { width: 17%; }
.center { text-align: center; }.right { text-align: right; }
.summary-wrap { width: 100%; border-collapse: collapse; margin-top: 7px; page-break-inside: avoid; }
.summary-wrap td { vertical-align: top; }
.summary-left { width: 52%; padding: 0 7px 0 4px; }
.summary-right { width: 48%; padding: 0; }
.vat-card { border: 1px solid #b9c7d8; padding: 7px; background: #f8fafc; }
.vat-rate { font-size: 10px; font-weight: bold; margin-bottom: 5px; }
.net-note { font-size: 8px; color: #5e6b7e; }
.amount-words { margin-top: 8px; padding-top: 6px; border-top: 1px dotted #7c8794; font-style: italic; }
.totals { width: 100%; border-collapse: collapse; }
.totals td { padding: 4px 3px; }
.totals .label { width: 64%; }.totals .value { width: 36%; text-align: right; }
.totals .grand td { border-top: 2px solid #315ee8; padding-top: 6px; font-size: 10px; font-weight: bold; }
.signature-table { width: 100%; border-collapse: collapse; margin-top: 7px; page-break-inside: avoid; }
.signature-table td { width: 50%; text-align: center; vertical-align: top; padding: 2px 8px; }
.signature-title { font-weight: bold; }.signature-role { margin-top: 1px; font-size: 8px; font-style: italic; color: #667085; }.signature-area { min-height: 57px; }
.digital-signature { display: inline-block; min-width: 175px; margin-top: 8px; padding: 7px 10px; border: 1px solid #7c8da3; border-radius: 2px; text-align: left; font-size: 7.5px; line-height: 1.35; background: #fbfcfe; }
.digital-signature .sign-name { font-size: 10px; font-weight: bold; }.digital-signature .signed { margin-top: 2px; }.digital-signature .bank { margin-top: 1px; }
.signature-note { margin-top: 3px; font-size: 7px; color: #555; }
.footer-note { position: fixed; left: 0; right: 0; bottom: 3mm; padding-top: 5px; border-top: 1px solid #9aa8b6; text-align: center; font-size: 6.8px; color: #596574; }
.page-number { position: fixed; left: 0; right: 0; bottom: 10mm; text-align: center; font-size: 7px; color: #667085; }
</style>
</head>
<body>
@php
    $vatRate = (float) ($order->vat_rate ?? 0);
    $vatAmount = (float) ($order->vat_amount ?? 0);
    $grandTotal = (float) ($order->total ?? 0);
    $totalBeforeVat = max(0, $grandTotal - $vatAmount);
    $taxFactor = 1 + ($vatRate / 100);
@endphp
<div class="page">
    <div class="header">
        <div class="title-wrap">
            <table class="title-inline">
                <tr>
                    <td class="logo-cell"><img class="logo" src="{{ public_path('images/techstore-logo.svg') }}" alt="TechStore"></td>
                    <td class="title-cell">
                        <div class="company">CÔNG TY TNHH TECHSTORE</div>
                        <h1>HÓA ĐƠN GIÁ TRỊ GIA TĂNG</h1>
                        <div class="subtitle">(Bản thể hiện của hóa đơn điện tử)</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <table class="invoice-ident">
        <tr>
            <td class="left">Mã hóa đơn: <strong>{{ $order->code }}</strong></td>
            <td class="right">Ngày lập: <strong>{{ now()->format('d/m/Y H:i:s') }}</strong></td>
        </tr>
    </table>

    <div class="section-title">THÔNG TIN ĐƠN VỊ BÁN HÀNG</div>
    <table class="seller">
        <tr><td class="label">Đơn vị bán hàng:</td><td class="value" colspan="3"><strong>CÔNG TY TNHH TECHSTORE</strong></td></tr>
        <tr><td class="label">Mã số thuế:</td><td class="value">049201000342</td><td class="label">Điện thoại:</td><td class="value">0905955162 / 0913031912</td></tr>
        <tr><td class="label">Địa chỉ:</td><td class="value" colspan="3">02 Phan Châu Trinh, Hội An, Đà Nẵng</td></tr>
        <tr><td class="label">Số tài khoản:</td><td class="value">1031993929</td><td class="label">Ngân hàng:</td><td class="value">Vietcombank</td></tr>
        <tr><td class="label">Chủ tài khoản:</td><td class="value" colspan="3">VO VAN KHOA</td></tr>
    </table>

    <div class="section-title">THÔNG TIN NGƯỜI MUA HÀNG</div>
    <table class="buyer">
        <tr><td class="label">Họ và tên:</td><td class="value">{{ $order->customer_name }}</td><td class="label">Số điện thoại:</td><td class="value">{{ $order->customer_phone ?: '—' }}</td></tr>
        <tr><td class="label">Email:</td><td class="value" colspan="3">{{ $order->customer_email ?: ($order->vat_email ?: '—') }}</td></tr>
        <tr><td class="label">Địa chỉ:</td><td class="value" colspan="3">{{ $order->customer_address ?: ($order->vat_address ?: '—') }}</td></tr>
        @if($order->vat_company_name)
        <tr><td class="label">Đơn vị:</td><td class="value">{{ $order->vat_company_name }}</td><td class="label">Mã số thuế:</td><td class="value">{{ $order->vat_tax_code ?: '—' }}</td></tr>
        @endif
        <tr><td colspan="4"><div class="payment-box"><strong>Hình thức thanh toán:</strong>
            @switch($order->payment)
                @case('paypal-sandbox') @case('paypal-demo') @case('paypal') PayPal @break
                @case('cod') Thanh toán khi nhận hàng @break
                @default {{ $order->payment ?: '—' }}
            @endswitch
        </div></td></tr>
    </table>

    <table class="items">
        <thead><tr><th class="index">STT</th><th class="description">Tên hàng hóa, dịch vụ</th><th class="unit">Đơn vị tính</th><th class="qty">Số lượng</th><th class="price">Đơn giá chưa thuế</th><th class="amount">Thành tiền chưa thuế</th></tr></thead>
        <tbody>
        @foreach($order->items as $index => $item)
            @php
                $grossUnitPrice = (float) $item->price;
                $grossLineTotal = (float) $item->total;
                $netUnitPrice = $taxFactor > 1 ? round($grossUnitPrice / $taxFactor) : $grossUnitPrice;
                $netLineTotal = $taxFactor > 1 ? round($grossLineTotal / $taxFactor) : $grossLineTotal;
            @endphp
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $item->name }}</td>
                <td class="center">Sản phẩm</td>
                <td class="center">{{ $item->quantity }}</td>
                <td class="right">{{ number_format($netUnitPrice, 0, ',', '.') }} VND</td>
                <td class="right">{{ number_format($netLineTotal, 0, ',', '.') }} VND</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="summary-wrap">
        <tr>
            <td class="summary-left">
                <div class="vat-card">
                    <div class="vat-rate">Thuế suất GTGT: {{ number_format($vatRate, 0) }}%</div>
                    <div class="net-note">Các mức giá hàng hóa trong bảng là giá <strong>chưa bao gồm thuế GTGT</strong>.</div>
                    <div class="amount-words">Số tiền viết bằng chữ: <strong>{{ number_format($grandTotal, 0, ',', '.') }} VND.</strong></div>
                </div>
            </td>
            <td class="summary-right">
                <table class="totals">
                    <tr><td class="label">Cộng tiền hàng chưa thuế:</td><td class="value">{{ number_format($totalBeforeVat, 0, ',', '.') }} VND</td></tr>
                    <tr><td class="label">Phí vận chuyển:</td><td class="value">{{ number_format((float) $order->total_shipping, 0, ',', '.') }} VND</td></tr>
                    <tr><td class="label">Tiền thuế GTGT:</td><td class="value">{{ number_format($vatAmount, 0, ',', '.') }} VND</td></tr>
                    <tr class="grand"><td class="label">Tổng tiền thanh toán:</td><td class="value">{{ number_format($grandTotal, 0, ',', '.') }} VND</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="signature-table">
        <tr>
            <td><div class="signature-title">Người mua hàng</div><div class="signature-role">(Ký, ghi rõ họ, tên)</div><div class="signature-area"></div></td>
            <td><div class="signature-title">Người bán hàng</div><div class="signature-role">(Ký, ghi rõ họ, tên)</div><div class="signature-area"><div class="digital-signature"><div class="sign-name">VO VAN KHOA</div><div class="signed">Đã ký điện tử</div><div class="bank">Vietcombank · STK 1031993929</div></div></div><div class="signature-note">Chữ ký hiển thị điện tử của người bán.</div></td>
        </tr>
    </table>

    <div class="page-number">Trang 1 / 1</div>
    <div class="footer-note">Hóa đơn do TechStore tạo từ dữ liệu đơn hàng.</div>
</div>
</body>
</html>
