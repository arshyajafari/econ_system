<?php

    namespace App\Enums;

    enum EmployeePosition: string {
        case ADMIN = 'admin';
        case MANAGER = 'manager';
        case VISITOR = 'visitor';
        case ACCOUNTANT = 'accountant';
        case WAREHOUSE = 'warehouse';
        case DELIVERY = 'delivery';
    }
