<?php

    namespace App\Enums;

    enum MessageStatus: string {
        case UNREAD = 'unread';
        case READ = 'read';
    }
