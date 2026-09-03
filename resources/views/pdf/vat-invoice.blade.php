<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<style>
@page { margin: 18mm 16mm 18mm 16mm; }
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #172033; line-height: 1.45; }
h1 { text-align:center; font-size:20px; margin:0 0 4px; }
.header { text-align:center; margin-bottom:18px; }
.company { font-size:16px; font-weight:bold; letter-spacing:.4px; }
.meta { width:100%; border-collapse:collapse; margin-bottom:16px; }
.meta td { padding:4px 6px; vertical-align:top; }
.meta .label { width:28%; font-weight:bold; }
table.items { width:100%; border-collapse:collapse; margin-top:10px; }
.items th { background:#eef2f7; border:1px solid #cfd6df; padding:7px 5px; font-weight:bold; }
.items td { border:1px solid #cfd6df; padding:7px 5px; }
.right { text-align:right; }
.center { text-align:center; }
.total { width:45%; margin-left:auto; margin-top:14px; border-collapse:collapse; }
.total td { padding:5px 0; }
.total .grand td { border-top:1px solid #333; padding-top:9px; font-size:13px; font-weight:bold; }
.note { margin-top:20px; padding:10px; border:1px solid #cfd6df; }
.footer { margin-top:28px; text-align:center; font-size:9px; color:#667085; }
</style>
</head>
<body>
<div class="header">
    <div class="company">TECHSTORE</div>
    <h1>HÓA ĐƠN VAT</h1>
    <div>Mã đơn hàng: <strong>{{ $order->code }}</strong></div>
</div>

<table class="meta">
<tr><td class="label">Ngày lập:</td><td>{{ optional($order->updated_at)->format('d/m/Y H:i') }}</td></tr>
<tr><td class="label">Khách hàng:</td><td>{{ $order->customer_name }}</td></tr>
<tr><td class="label">Số điện thoại:</td><td>{{ $order->customer_phone }}</td></tr>
<tr><td class="label">Email:</td><td>{{ $order->customer_email ?: $order->vat_email }}</td></tr>
<tr><td class="label">Tên công ty/đơn vị:</td><td>{{ $order->vat_company_name }}</td></tr>
<tr><td class="label">Mã số thuế:</td><td>{{ $order->vat_tax_code }}</td></tr>
<tr><td class="label">Địa chỉ xuất hóa đơn:</td><td>{{ $order->vat_address }}</td></tr>
</table>

<table class="items">
<thead><tr><th class="center" style="width:7%">STT</th><th>Sản phẩm</th><th class="center" style="width:12%">SL</th><th class="right" style="width:20%">Đơn giá</th><th class="right" style="width:20%">Thành tiền</th></tr></thead>
<tbody>
@foreach($order->items as $index => $item)
<tr><td class="center">{{ $index + 1 }}</td><td>{{ $item->name }}</td><td class="center">{{ $item->quantity }}</td><td class="right">{{ number_format($item->price, 0, ',', '.') }} ₫</td><td class="right">{{ number_format($item->total, 0, ',', '.') }} ₫</td></tr>
@endforeach
</tbody>
</table>

<table class="total">
<tr><td>Tiền hàng</td><td class="right">{{ number_format($order->subtotal, 0, ',', '.') }} ₫</td></tr>
<tr><td>Phí vận chuyển</td><td class="right">{{ number_format($order->total_shipping, 0, ',', '.') }} ₫</td></tr>
<tr><td>Thuế VAT ({{ number_format((float)$order->vat_rate, 0) }}%)</td><td class="right">{{ number_format($order->vat_amount, 0, ',', '.') }} ₫</td></tr>
<tr class="grand"><td>TỔNG THANH TOÁN</td><td class="right">{{ number_format($order->total, 0, ',', '.') }} ₫</td></tr>
</table>

<div class="note"><strong>Trạng thái:</strong> Đơn hàng đã được Admin/Quản trị viên xác nhận. Đây là chứng từ PDF do TechStore tạo từ thông tin yêu cầu xuất VAT của đơn hàng.</div>
<div class="footer">TechStore · Cảm ơn quý khách đã mua hàng.</div>
</body>
</html>
