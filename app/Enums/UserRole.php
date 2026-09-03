<?php
namespace App\Enums;
enum UserRole:string{case ADMIN='admin';case SALES='sales';case TECHNICAL='technical';case CUSTOMER_SERVICE='customer_service';case STAFF='staff';case CUSTOMER='customer';public function label():string{return match($this){self::ADMIN=>'Quản trị viên',self::SALES=>'Bán hàng',self::TECHNICAL=>'Kỹ thuật',self::CUSTOMER_SERVICE=>'Chăm sóc khách hàng',self::STAFF=>'Nhân viên',self::CUSTOMER=>'Khách hàng'};}public function isBackOffice():bool{return in_array($this,[self::ADMIN,self::SALES,self::TECHNICAL,self::CUSTOMER_SERVICE,self::STAFF],true);}}
