<?php

    namespace App\Enums;

    enum CustomerStatus: string {
        case ACTIVE = 'active';
        case INACTIVE = 'inactive';
        case BLOCKED = 'blocked';

        public function label(): string {
            return match ($this) {
                self::ACTIVE => 'فعال',
                self::INACTIVE => 'غیرفعال',
                self::BLOCKED => 'مسدود',
            };
        }
    }
