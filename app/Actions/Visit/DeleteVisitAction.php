<?php

namespace App\Actions\Visit;

use App\Models\Visit;

class DeleteVisitAction
{
    public function execute(Visit $visit): void
    {
        $visit->delete();
    }
}
