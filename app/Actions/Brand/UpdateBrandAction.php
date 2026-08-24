<?php

    namespace App\Actions\Brand;

    use App\Models\Brand;
    use Illuminate\Support\Facades\DB;

    class UpdateBrandAction {
        public function execute(Brand $brand, array $data): Brand {
            return DB::transaction(function () use (
                $brand, $data
            ) {
                $brand->fill($data);
                $brand->save();

                return $brand->fresh(Brand::DEFAULT_RELATIONS);
            });
        }
    }
