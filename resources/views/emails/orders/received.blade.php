<!doctype html>
<html lang="vi">
<head><meta charset="utf-8"><title>Đơn hàng đang chờ xử lý</title></head>
<body style="margin:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#172033;line-height:1.6">
<div style="max-width:680px;margin:32px auto;background:#fff;border-radius:14px;padding:32px;box-shadow:0 4px 18px rgba(0,0,0,.06)">
<h2 style="margin-top:0">TechStore</h2>
<p>Xin chào <strong>{{ $order->customer_name }}</strong>,</p>
<p>TechStore đã ghi nhận đơn hàng <strong>{{ $order->code }}</strong> và thanh toán/đặt hàng của bạn đã được tiếp nhận thành công.</p>
<div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:16px;margin:20px 0"><strong>Trạng thái Mail: Chờ xử lý</strong><br>Đơn hàng đang chờ Admin hoặc Quản trị viên kiểm tra và xác nhận.</div>
<p><strong>Tổng thanh toán:</strong> {{ number_format($order->total, 0, ',', '.') }} ₫</p>
<p>Bạn sẽ nhận được Email tiếp theo ngay sau khi đơn hàng được xác nhận.</p>
<p>Trân trọng,<br><strong>Đội ngũ TechStore</strong></p>
</div>
</body>
</html>
