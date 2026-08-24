<?php

    namespace App\Enums;

    enum EmploymentType: string {
        case FULL_TIME = 'full_time';
        case PART_TIME = 'part_time';
        case CONTRACT = 'contract';
        case TEMPORARY = 'temporary';

        public function label(): string {
            return match ($this) {
                self::FULL_TIME => 'تمام وقت',
                self::PART_TIME => 'نیم وقت',
                self::CONTRACT => 'قراردادی',
                self::TEMPORARY => 'موقت',
            };
        }
    }
