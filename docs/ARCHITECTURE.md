# Kiến trúc TechStore

## Nguyên tắc

- Laravel xử lý nghiệp vụ, xác thực, phân quyền và truy cập MySQL.
- Vue 3 + Inertia xử lý giao diện và điều hướng phía khách hàng.
- Bootstrap 5 là hệ thống giao diện chính; không dùng Tailwind cho UI mới.
- MySQL Server chạy độc lập, không phụ thuộc XAMPP hoặc Apache.
- Controller mỏng; nghiệp vụ đặt trong Service/Action.
- Repository chỉ dùng khi cần tách lớp truy cập dữ liệu hoặc tái sử dụng truy vấn.
- Form Request chịu trách nhiệm kiểm tra dữ liệu đầu vào.
- Policy/Middleware chịu trách nhiệm phân quyền.
- Enum dùng cho trạng thái và giá trị nghiệp vụ có tập hữu hạn.

## Luồng backend

`Route -> Middleware -> Controller -> FormRequest -> Service/Action -> Repository -> Model -> MySQL`

## Luồng frontend

`Page -> Layout -> Component -> Inertia request -> Laravel`

## Phân khu vực

- `resources/js/layouts/ClientLayout.vue`: giao diện khách hàng.
- `resources/js/layouts/AdminLayout.vue`: giao diện quản trị.
- `resources/js/pages/admin/*`: các trang quản trị.
- `app/Enums`: trạng thái và loại nghiệp vụ.
- `app/Contracts`: hợp đồng giữa các lớp.
- `app/Repositories`: truy cập dữ liệu khi cần abstraction.
- `app/Services`: nghiệp vụ ứng dụng.
- `app/Http/Requests`: validation.
- `app/Http/Middleware`: xác thực/phân quyền.

## Nguyên tắc database

- Khóa chính dùng `id`.
- Khóa ngoại có index và ràng buộc phù hợp.
- Tiền tệ dùng kiểu số chính xác, không dùng floating point.
- Sản phẩm/đơn hàng ưu tiên soft delete khi nghiệp vụ yêu cầu lưu lịch sử.
- Trạng thái dùng Enum thay vì chuỗi rải rác trong controller.
- Không lưu mật khẩu hoặc thông tin thanh toán nhạy cảm dạng rõ.
