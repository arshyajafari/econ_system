<?php

    namespace App\Actions\Brand;

    use App\Models\Brand;

    class ShowBrandAction {
        public function execute(Brand $brand): Brand {
            return $brand->fresh(Brand::DEFAULT_RELATIONS);
        }
    }
