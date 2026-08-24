<?php

    namespace App\Enums;

    enum InventoryMovementType: string {
        case IN = 'in';
        case OUT = 'out';
        case ADJUSTMENT_IN = 'adjustment_in';
        case ADJUSTMENT_OUT = 'adjustment_out';
    }
