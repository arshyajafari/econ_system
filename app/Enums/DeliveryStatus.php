<?php

    namespace App\Enums;

    enum DeliveryStatus: string {
        case PENDING = 'pending';
        case PREPARING = 'preparing';
        case SHIPPED = 'shipped';
        case DELIVERED = 'delivered';
        case CANCELLED = 'cancelled';
    }
