<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PruneEmployeeLocationHistory extends Command
{
    protected $signature = 'employee-locations:prune-history';

    protected $description = 'Deprecated: use the audited admin data-cleanup interface';

    public function handle(): int
    {
        $this->error('اجرای مستقیم پاک‌سازی غیرفعال است. از بخش «مدیریت و پاک‌سازی اطلاعات» در پنل مدیر استفاده کنید.');

        return self::FAILURE;
    }
}
