<?php


    namespace App\Enums;


    use App\Contracts\Enums\HasColor;
    use App\Contracts\Enums\HasIcon;
    use App\Contracts\Enums\HasLabel;
    use App\Traits\HasOptions;

    enum UserStatus: string implements HasLabel, HasColor, HasIcon {
        use HasOptions;

        case ACTIVE = 'active';
        case INACTIVE = 'inactive';
        case LOCKED = 'locked';
        case SUSPENDED = 'suspended';

        public function label(): string {
            return match ($this) {
                self::ACTIVE => 'فعال',
                self::INACTIVE => 'غیرفعال',
                self::LOCKED => 'قفل شده',
                self::SUSPENDED => 'معلق',
            };
        }

        public function color(): string {
            return match ($this) {
                self::ACTIVE => 'success',
                self::INACTIVE => 'secondary',
                self::LOCKED => 'warning',
                self::SUSPENDED => 'danger',
            };
        }

        public function icon(): string {
            return match ($this) {
                self::ACTIVE => 'check-circle',
                self::INACTIVE => 'x-circle',
                self::LOCKED => 'lock',
                self::SUSPENDED => 'ban',
            };
        }
    }
