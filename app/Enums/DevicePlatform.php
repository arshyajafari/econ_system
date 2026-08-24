<?php

    namespace App\Enums;

    enum DevicePlatform: string {
        case WEB = 'web';
        case ANDROID = 'android';
        case IOS = 'ios';
        case WINDOWS = 'windows';
        case MACOS = 'macos';
        case LINUX = 'linux';
    }
