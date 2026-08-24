<?php

    namespace App\Enums;

    enum DoctorVisitStatus: string {
        case PLANNED = 'planned';
        case COMPLETED = 'completed';
        case CANCELLED = 'cancelled';
    }
