<?php

    namespace App\Enums;

    enum CustomerTransactionType: string {
        case DEBIT = 'debit';
        case CREDIT = 'credit';
    }
