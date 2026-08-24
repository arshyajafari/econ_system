<?php

    namespace App\Actions\ProductCategory;

    use App\Actions\BaseAction;
    use App\Models\ProductCategory;
    use Illuminate\Support\Facades\DB;

    class UpdateProductCategoryAction extends BaseAction {
        public function execute(ProductCategory $category, array $data): ProductCategory {
            return DB::transaction(function () use (
                $category, $data
            ) {
                $category->fill($data);
                $category->save();

                return $category->fresh(ProductCategory::DEFAULT_RELATIONS);
            });

        }
    }
