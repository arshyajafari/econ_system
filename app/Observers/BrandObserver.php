<?php

    namespace App\Observers;

    use App\Models\Brand;

    class BrandObserver {
        public function creating(Brand $brand): void { }

        public function updating(Brand $brand): void { }

        public function deleting(Brand $brand): void { }
    }
