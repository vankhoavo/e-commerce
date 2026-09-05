<!doctype html>
<html lang="vi">
<head><meta charset="utf-8"><title>Đơn hàng đã được xử lý</title></head>
<body style="margin:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#172033;line-height:1.6">
<div style="max-width:680px;margin:32px auto;background:#fff;border-radius:14px;padding:32px;box-shadow:0 4px 18px rgba(0,0,0,.06)">
<h2 style="margin-top:0">TechStore</h2>
<p>Xin chào <strong>{{ $order->customer_name }}</strong>,</p>
<p>Đơn hàng <strong>{{ $order->code }}</strong> của bạn đã được Admin/Quản trị viên xác nhận.</p>
<div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;padding:16px;margin:20px 0"><strong>Trạng thái Mail: Đơn hàng đã được xử lý và đang chờ vận chuyển</strong><br>Đơn hàng đã được duyệt, tồn kho đã được cập nhật và đang chờ bàn giao cho đơn vị vận chuyển.</div>
<p><strong>Tổng thanh toán:</strong> {{ number_format($order->total, 0, ',', '.') }} ₫</p>
@if($order->vat_invoice_requested)
<p>Yêu cầu hóa đơn VAT của bạn đã được ghi nhận. Email hóa đơn VAT sẽ được gửi sau khi đơn hàng được xác nhận.</p>
@endif
<p>Trân trọng,<br><strong>Đội ngũ TechStore</strong></p>
</div>
</body>
</html>
