# Giai đoạn 2 – Xác thực và phân quyền

## Mục tiêu

- Đăng nhập/đăng ký bằng Laravel Fortify.
- Xác minh Email bằng OTP.
- Quên mật khẩu bằng Email OTP.
- Xác thực hai bước dùng Fortify.
- Tài khoản có 3 vai trò: `admin`, `staff`, `customer`.
- Tài khoản bị khóa (`is_active = false`) không được truy cập khu vực quản trị.
- Hỗ trợ đăng nhập bằng Google OAuth 2.0 / OpenID Connect.

## Khôi phục mật khẩu bằng Email OTP

Luồng khôi phục mật khẩu của TechStore dùng mã OTP 6 chữ số gửi qua Email:

1. Người dùng nhập Email đã đăng ký.
2. Hệ thống tạo OTP mới và vô hiệu hóa OTP chưa dùng trước đó của tài khoản.
3. OTP có hiệu lực trong 10 phút và được lưu dưới dạng mã băm.
4. Người dùng nhập OTP để xác minh.
5. Sau khi xác minh thành công, người dùng tạo mật khẩu mới theo chính sách mật khẩu chung.
6. OTP đã xác minh được đánh dấu đã sử dụng và phiên khôi phục được xóa sau khi đổi mật khẩu.

Hệ thống giới hạn số lần yêu cầu và số lần nhập OTP để giảm nguy cơ lạm dụng. Với Email không tồn tại, phản hồi không tiết lộ tài khoản có tồn tại hay không.

## Google OAuth

Ứng dụng dùng luồng Authorization Code và tự kiểm tra `state` để chống CSRF.
Google chỉ được liên kết khi Google trả về email đã xác minh.

### Biến môi trường

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

### Google Cloud Console

Tạo OAuth Client loại Web application và khai báo chính xác URI callback:

```text
http://localhost:8000/auth/google/callback
```

Khi chạy production, đổi sang tên miền thật và HTTPS.

## Tạo tài khoản quản trị

Giai đoạn này chưa tạo sẵn tài khoản quản trị và không lưu mật khẩu mẫu trong mã nguồn.
Sau khi đăng ký tài khoản bằng giao diện, quản trị viên có thể được nâng vai trò thông qua công cụ quản trị ở giai đoạn tiếp theo.

## Kiểm tra local

```powershell
php artisan migrate
php artisan test
npm run build
```

Kiểm tra các luồng:

1. Đăng ký tài khoản mới → vai trò mặc định `customer`.
2. Đăng nhập bằng email/mật khẩu.
3. Quên mật khẩu → nhận Email OTP → xác minh → đặt mật khẩu mới.
4. Xác minh Email.
5. Bật xác thực hai bước.
6. Đăng nhập Google.
7. Customer/staff truy cập `/admin` → `403`.
8. Admin hoạt động truy cập `/admin` → thành công.
9. Admin bị khóa → `403`.
