<?php

    namespace App\Enums;

    enum EmployeeStatus: string {
        case ACTIVE = 'active';
        case INACTIVE = 'inactive';
        case TERMINATED = 'terminated';
        case RETIRED = 'retired';

        public function label(): string {
            return match ($this) {
                self::ACTIVE => 'فعال',
                self::INACTIVE => 'غیرفعال',
                self::TERMINATED => 'فسخ شده',
                self::RETIRED => 'بازنشسته',
            };
        }
    }
