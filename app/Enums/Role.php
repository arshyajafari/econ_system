<?php

    namespace app\Enums;

    enum Role: string {
        case ADMIN = 'admin';
        case SALES_VISITOR = 'sales visitor';
        case SCIENTIFIC_VISITOR = 'scientific visitor';
        case ACCOUNTANT = 'accountant';
        case SETTLEMENT_OPERATOR = 'settlement operator';
        case DELIVERY_OPERATOR = 'delivery operator';
    }
