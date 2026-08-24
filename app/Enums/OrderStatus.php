<?php

    namespace App\Enums;

    enum OrderStatus: string {
        case DRAFT = 'draft';
        case PENDING = 'pending';
        case CONFIRMED = 'confirmed';
        case CANCELLED = 'cancelled';
        case COMPLETED = 'completed';
    }
