<?php

    namespace App\Actions\ProductCategory;

    use App\Actions\BaseAction;
    use App\Exceptions\BusinessRuleException;
    use App\Models\ProductCategory;
    use Illuminate\Support\Facades\DB;

    class DeleteProductCategoryAction extends BaseAction {
        public function execute(ProductCategory $category): void {
            DB::transaction(function () use ($category) {
                $category = ProductCategory::query()->lockForUpdate()->findOrFail($category->id);

                if ($category->children()->exists()) {
                    throw new BusinessRuleException('امکان حذف دسته‌بندی دارای زیرمجموعه وجود ندارد.');
                }

                if ($category->products()->exists()) {
                    throw new BusinessRuleException('امکان حذف دسته‌بندی دارای محصول وجود ندارد.');
                }

                $category->delete();
            });
        }
    }
