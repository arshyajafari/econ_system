<?php

    namespace App\Enums;

    enum VisitStatus: string {
        case DRAFT = 'draft';
        case COMPLETED = 'completed';
        case CANCELLED = 'cancelled';
    }
