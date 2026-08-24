<?php

    namespace App\Enums;

    enum ProductStatus: string {
        case ACTIVE = 'active';
        case INACTIVE = 'inactive';
        case DISCONTINUED = 'discontinued';
        case PENDING = 'pending';

        public function label(): string {
            return match ($this) {
                self::ACTIVE => 'فعال',
                self::INACTIVE => 'غیرفعال',
                self::DISCONTINUED => 'تولید متوقف شده',
                self::PENDING => 'در انتظار',
            };
        }
    }
