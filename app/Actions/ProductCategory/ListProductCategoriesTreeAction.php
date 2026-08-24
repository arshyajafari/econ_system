<?php

    namespace App\Actions\ProductCategory;

    use App\Models\ProductCategory;
    use Illuminate\Database\Eloquent\Collection;

    class ListProductCategoriesTreeAction {
        public function execute(): Collection {
            return ProductCategory::query()->whereNull('parent_id')->with('childrenRecursive')->orderBy('sort_order')
                ->orderBy('title')->get();
        }
    }
