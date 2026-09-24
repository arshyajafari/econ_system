<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case ADMINISTRATIVE = 'administrative';
    case TRANSPORT = 'transport';
    case ADVERTISING = 'advertising';
    case SALARY = 'salary';
    case RENT = 'rent';
    case SUPPLIES = 'supplies';
    case HOSPITALITY = 'hospitality';
    case OTHER = 'other';
}
