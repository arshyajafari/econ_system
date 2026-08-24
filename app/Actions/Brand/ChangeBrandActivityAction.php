<?php

    namespace App\Actions\Brand;

    use App\Models\Brand;
    use Illuminate\Support\Facades\DB;

    class ChangeBrandActivityAction {
        public function execute(Brand $brand, bool $isActive): Brand {
            return DB::transaction(function () use (
                $brand, $isActive
            ) {
                if ($brand->is_active === $isActive) {
                    return $brand;
                }

                $brand->update([
                    'is_active' => $isActive,
                ]);

                return $brand->fresh(Brand::DEFAULT_RELATIONS);
            });
        }
    }
