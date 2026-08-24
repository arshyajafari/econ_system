<?php

    namespace App\Enums;

    enum OrderReturnStatus: string {
        case DRAFT = 'draft';
        case PENDING = 'pending';
        case CONFIRMED = 'confirmed';
        case COMPLETED = 'completed';
        case CANCELLED = 'cancelled';
    }
