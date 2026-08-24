<?php

    namespace App\Actions\ProductCategory;

    use App\Actions\BaseAction;
    use App\Models\ProductCategory;
    use Illuminate\Support\Facades\DB;

    class ChangeProductCategoryActivityAction extends BaseAction {
        public function execute(ProductCategory $category, bool $isActive): ProductCategory {
            return DB::transaction(function () use (
                $category, $isActive
            ) {
                if ($category->is_active === $isActive) {
                    return $category;
                }

                $category->update([
                    'is_active' => $isActive,
                ]);

                return $category->fresh(ProductCategory::DEFAULT_RELATIONS);
            });
        }
    }
