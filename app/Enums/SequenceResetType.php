<?php

    namespace App\Enums;

    enum SequenceResetType: string {
        case NONE = 'none';
        case YEAR = 'year';
        case MONTH = 'month';
    }
