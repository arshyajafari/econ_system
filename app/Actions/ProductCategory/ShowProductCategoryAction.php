<?php

    namespace App\Actions\ProductCategory;

    use App\Models\ProductCategory;

    class ShowProductCategoryAction {
        public function execute(ProductCategory $category): ProductCategory {
            return $category->fresh(ProductCategory::DEFAULT_RELATIONS);
        }
    }
