# Chạy TechStore trên Windows

## 1. Yêu cầu

- PHP 8.3 trở lên
- Composer
- Node.js LTS
- MySQL Server 8.x hoặc tương thích
- Không cần XAMPP
- Không cần Apache

## 2. Tạo database MySQL

```sql
CREATE DATABASE techstore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 3. Cấu hình môi trường

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Mở `.env` và điền `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` theo MySQL Server riêng của máy.

## 4. Cài dependency

```powershell
composer install
npm install
```

## 5. Kiểm tra database

```powershell
php artisan migrate
```

## 6. Chạy local

Terminal 1:

```powershell
php artisan serve
```

Terminal 2:

```powershell
npm run dev
```

Mở `http://127.0.0.1:8000`.

## Giai đoạn 1 đã chuẩn bị

- MySQL làm database mặc định trong `.env.example`.
- Bootstrap 5 + Bootstrap Icons được nạp toàn cục.
- Layout Client và Admin tách biệt.
- Middleware `admin` bảo vệ khu vực quản trị.
- User có `role`, `phone`, `is_active`, `avatar`.
- Enum `UserRole` chuẩn hóa vai trò.
- Repository Contract/Base Repository làm nền cho Clean Architecture.
- Dashboard quản trị và trang chủ TechStore đã có giao diện responsive.
