<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case SENIOR_STAFF = 'senior_staff';
    case STAFF = 'staff';
    case SALES_STAFF = 'sales_staff';
    case TECHNICAL_STAFF = 'technical_staff';
    case CUSTOMER_SERVICE_STAFF = 'customer_service_staff';
    case WARRANTY_STAFF = 'warranty_staff';
    case SEO_STAFF = 'seo_staff';
    case ADVERTISING_STAFF = 'advertising_staff';
    case CONTENT_STAFF = 'content_staff';
    case CHAT_HOTLINE_STAFF = 'chat_hotline_staff';
    case COMPLAINT_STAFF = 'complaint_staff';
    case INVENTORY_STAFF = 'inventory_staff';
    case SALES_COORDINATOR_STAFF = 'sales_coordinator_staff';
    case CUSTOMER = 'customer';

    /** @return list<self> */
    public static function employeeRoles(): array
    {
        return [
            self::SENIOR_STAFF,
            self::SALES_STAFF,
            self::TECHNICAL_STAFF,
            self::CUSTOMER_SERVICE_STAFF,
            self::WARRANTY_STAFF,
            self::SEO_STAFF,
            self::ADVERTISING_STAFF,
            self::CONTENT_STAFF,
            self::CHAT_HOTLINE_STAFF,
            self::COMPLAINT_STAFF,
            self::INVENTORY_STAFF,
            self::SALES_COORDINATOR_STAFF,
            self::STAFF,
        ];
    }

    /** @return list<self> */
    public static function subordinateRoles(): array
    {
        return [
            self::SALES_STAFF,
            self::TECHNICAL_STAFF,
            self::CUSTOMER_SERVICE_STAFF,
            self::WARRANTY_STAFF,
            self::SEO_STAFF,
            self::ADVERTISING_STAFF,
            self::CONTENT_STAFF,
            self::CHAT_HOTLINE_STAFF,
            self::COMPLAINT_STAFF,
            self::INVENTORY_STAFF,
            self::SALES_COORDINATOR_STAFF,
        ];
    }

    /** @return list<self> */
    public static function creatableSubordinateRoles(): array
    {
        return [
            self::SEO_STAFF,
            self::ADVERTISING_STAFF,
            self::CONTENT_STAFF,
            self::CHAT_HOTLINE_STAFF,
            self::COMPLAINT_STAFF,
            self::INVENTORY_STAFF,
            self::SALES_STAFF,
            self::SALES_COORDINATOR_STAFF,
            self::TECHNICAL_STAFF,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Quản trị viên',
            self::SENIOR_STAFF, self::STAFF => 'Nhân viên cấp cao',
            self::SALES_STAFF => 'Nhân viên bán hàng',
            self::TECHNICAL_STAFF => 'Nhân viên kỹ thuật',
            self::CUSTOMER_SERVICE_STAFF => 'Nhân viên chăm sóc khách hàng',
            self::WARRANTY_STAFF => 'Nhân viên bảo hành',
            self::SEO_STAFF => 'Nhân viên SEO',
            self::ADVERTISING_STAFF => 'Nhân viên Quảng Cáo',
            self::CONTENT_STAFF => 'Nhân viên sáng tạo nội dung',
            self::CHAT_HOTLINE_STAFF => 'Nhân viên trực chat/hotline',
            self::COMPLAINT_STAFF => 'Nhân viên xử lý khiếu nại',
            self::INVENTORY_STAFF => 'Nhân viên quản lý kho',
            self::SALES_COORDINATOR_STAFF => 'Nhân viên điều phối giao hàng',
            self::CUSTOMER => 'Khách hàng',
        };
    }
}
