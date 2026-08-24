<?php

    namespace App\Traits;

    trait HasOptions {
        public static function values(): array {
            return array_column(self::options(), 'value');
        }

        public static function options(): array {
            return array_map(fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ], self::cases());
        }

        public static function labels(): array {
            return array_column(self::options(), 'label');
        }
    }
