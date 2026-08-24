<?php

    namespace App\Enums;

    enum DoctorStatus: string {
        case ACTIVE = 'active';
        case INACTIVE = 'inactive';
        case SUSPENDED = 'suspended';
    }
