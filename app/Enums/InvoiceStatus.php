<?php

    namespace App\Enums;

    enum InvoiceStatus: string {
        case DRAFT = 'draft';
        case ISSUED = 'issued';
        case CANCELLED = 'cancelled';
    }
