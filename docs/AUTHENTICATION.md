# Giai đoạn 2 – Xác thực và phân quyền

## Mục tiêu

- Đăng nhập/đăng ký bằng Laravel Fortify.
- Email verification, quên mật khẩu và xác thực hai bước tiếp tục dùng Fortify.
- Tài khoản có 3 vai trò: `admin`, `staff`, `customer`.
- Tài khoản bị khóa (`is_active = false`) không được truy cập khu vực quản trị.
- Hỗ trợ đăng nhập bằng Google OAuth 2.0 / OpenID Connect.

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
3. Quên mật khẩu.
4. Xác minh email.
5. Bật xác thực hai bước.
6. Đăng nhập Google.
7. Customer/staff truy cập `/admin` → `403`.
8. Admin hoạt động truy cập `/admin` → thành công.
9. Admin bị khóa → `403`.
