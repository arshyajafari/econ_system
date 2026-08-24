<?php

    namespace App\Enums;

    enum InventoryAdjustmentType: string {
        case INCREASE = 'increase';
        case DECREASE = 'decrease';
    }
