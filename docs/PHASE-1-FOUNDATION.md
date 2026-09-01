# Giai đoạn 1 — Foundation

## Mục tiêu

Thiết lập nền tảng kỹ thuật cho website thương mại điện tử bán laptop và linh kiện điện tử.

## Công nghệ

- Laravel 13
- PHP 8.4+
- Vue 3
- Inertia.js
- MySQL 8+
- Bootstrap 5
- Bootstrap Icons
- Vite

Không sử dụng XAMPP hoặc Apache.

## Kiến trúc

```text
Browser
  -> Laravel Route
  -> Middleware / Authorization
  -> Controller
  -> Form Request
  -> Service / Action
  -> Repository
  -> Eloquent Model
  -> MySQL
```

Frontend:

```text
Inertia Page
  -> Layout
  -> Reusable Components
  -> Inertia Form / Router
  -> Laravel Endpoint
```

## Phân vùng giao diện

- Client: `resources/js/layouts/ClientLayout.vue`
- Admin: `resources/js/layouts/AdminLayout.vue`
- Auth: `resources/js/layouts/AuthLayout.vue`
- Settings: `resources/js/layouts/settings/`

## Phân quyền nền tảng

`UserRole` gồm:

- `admin`
- `staff`
- `customer`

Middleware `admin` chỉ cho phép tài khoản có role `admin` truy cập khu vực quản trị.

## Cơ sở dữ liệu

Cấu hình mẫu `.env.example` sử dụng MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=techstore
DB_USERNAME=root
DB_PASSWORD=
```

Các trường nền tảng của người dùng:

- `phone`
- `role`
- `is_active`
- `avatar`

Role mặc định khi tạo tài khoản là `customer`.

## Chạy local

Terminal 1:

```powershell
npm install
npm run dev
```

Terminal 2:

```powershell
php artisan serve
```

Nếu chạy production build:

```powershell
npm run build
```

Sau build phải tồn tại:

```text
public/build/manifest.json
```

## Quy tắc Clean Code

- Không đặt nghiệp vụ phức tạp trong Route.
- Controller mỏng.
- Validation dùng Form Request.
- Nghiệp vụ dùng Service/Action.
- Truy cập dữ liệu có thể tách qua Repository khi cần.
- Dùng Enum cho trạng thái/role thay vì chuỗi rải rác.
- Không lưu mật khẩu dạng rõ.
- Không commit `.env` hoặc thông tin bí mật.
- Giao diện dùng Bootstrap utility/component thay vì viết CSS trùng lặp.
