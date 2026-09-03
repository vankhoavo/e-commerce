<!doctype html>
<html lang="vi">
<head><meta charset="utf-8"><title>Hóa đơn VAT TechStore</title></head>
<body style="margin:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#172033;line-height:1.6">
<div style="max-width:760px;margin:32px auto;background:#fff;border-radius:14px;padding:32px;box-shadow:0 4px 18px rgba(0,0,0,.06)">
<h2 style="margin:0 0 4px">TECHSTORE</h2>
<h1 style="font-size:24px;margin:0 0 20px">HÓA ĐƠN VAT</h1>
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:16px;margin-bottom:20px"><strong>Trạng thái hóa đơn: Đã xác nhận</strong><br>Hóa đơn VAT được gửi sau khi Admin hoặc Quản trị viên xác nhận đơn hàng.</div>
<table style="width:100%;border-collapse:collapse;margin-bottom:20px">
<tr><td style="padding:7px 0"><strong>Mã đơn hàng</strong></td><td style="padding:7px 0">{{ $order->code }}</td></tr>
<tr><td style="padding:7px 0"><strong>Khách hàng</strong></td><td style="padding:7px 0">{{ $order->customer_name }}</td></tr>
<tr><td style="padding:7px 0"><strong>Tên công ty/đơn vị</strong></td><td style="padding:7px 0">{{ $order->vat_company_name }}</td></tr>
<tr><td style="padding:7px 0"><strong>Mã số thuế</strong></td><td style="padding:7px 0">{{ $order->vat_tax_code }}</td></tr>
<tr><td style="padding:7px 0"><strong>Địa chỉ</strong></td><td style="padding:7px 0">{{ $order->vat_address }}</td></tr>
<tr><td style="padding:7px 0"><strong>Email nhận hóa đơn</strong></td><td style="padding:7px 0">{{ $order->vat_email }}</td></tr>
</table>
<table style="width:100%;border-collapse:collapse">
<tr><td style="padding:8px 0">Tiền hàng</td><td style="padding:8px 0;text-align:right">{{ number_format($order->subtotal, 0, ',', '.') }} ₫</td></tr>
<tr><td style="padding:8px 0">Thuế VAT</td><td style="padding:8px 0;text-align:right">{{ number_format($order->vat_amount, 0, ',', '.') }} ₫ ({{ number_format((float)$order->vat_rate, 0) }}%)</td></tr>
<tr style="font-size:18px"><td style="padding:12px 0;border-top:1px solid #ddd"><strong>Tổng thanh toán</strong></td><td style="padding:12px 0;border-top:1px solid #ddd;text-align:right"><strong>{{ number_format($order->total, 0, ',', '.') }} ₫</strong></td></tr>
</table>
<p style="margin-top:24px">Cảm ơn bạn đã mua hàng tại TechStore.</p>
<p>Trân trọng,<br><strong>Đội ngũ TechStore</strong></p>
</div>
</body>
</html>
