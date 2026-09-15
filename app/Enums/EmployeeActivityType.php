<?php

namespace App\Enums;

enum EmployeeActivityType: string
{
    case SALES_VISITOR = 'sales_visitor';
    case SCIENTIFIC_VISITOR = 'scientific_visitor';
    case DELIVERY_OPERATOR = 'delivery_operator';
    case SETTLEMENT_OPERATOR = 'settlement_operator';
    case ACCOUNTANT = 'accountant';
    case WAREHOUSE_OPERATOR = 'warehouse_operator';
    case ADMIN = 'admin';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SALES_VISITOR => 'بازاریاب',
            self::SCIENTIFIC_VISITOR => 'ویزیتور علمی',
            self::DELIVERY_OPERATOR => 'مسئول تحویل',
            self::SETTLEMENT_OPERATOR => 'مسئول تسویه',
            self::ACCOUNTANT => 'حسابدار',
            self::WAREHOUSE_OPERATOR => 'انباردار',
            self::ADMIN => 'مدیر / ادمین',
            self::OTHER => 'سایر',
        };
    }
}
