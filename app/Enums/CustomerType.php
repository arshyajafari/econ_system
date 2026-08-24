<?php

    namespace App\Enums;

    enum CustomerType: string {
        case PHARMACY = 'pharmacy';
        case CLINIC = 'clinic';
        case HOSPITAL = 'hospital';
        case WHOLESALER = 'wholesaler';
        case STORE = 'store';
        case OTHER = 'other';
    }
