<?php


    namespace App\Enums;

    enum DeviceStatus: string {
        case ACTIVE = 'active';
        case BLOCKED = 'blocked';
        case LOGGED_OUT = 'logged_out';
    }
