<?php

    namespace App\Actions\Sample;

    use App\Models\Sample;

    class ShowSampleAction {
        public function execute(Sample $sample): Sample {
            return $sample->fresh(Sample::DEFAULT_RELATIONS);
        }
    }
