<?php

    namespace App\Observers;

    use App\Models\ProductCategory;

    class ProductCategoryObserver {
        public function creating(ProductCategory $category): void { }
    }
