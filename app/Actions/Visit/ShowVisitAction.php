<?php

    namespace App\Actions\Visit;

    use App\Models\Visit;

    class ShowVisitAction {
        public function execute(Visit $visit): Visit {
            return $visit->fresh([
                ...Visit::DEFAULT_RELATIONS,
                'samples',
            ]);
        }
    }
