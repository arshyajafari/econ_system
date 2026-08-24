<?php

    namespace App\Actions\ProductCategory;

    use App\Actions\BaseAction;
    use App\Exceptions\BusinessException;
    use App\Models\ProductCategory;
    use Illuminate\Support\Facades\DB;

    class DeleteProductCategoryAction extends BaseAction {
        public function execute(ProductCategory $category): void {

            DB::transaction(function () use ($category) {
                if ($category->children()->exists()) {
                    throw new BusinessException('امکان حذف دسته‌بندی دارای زیرمجموعه وجود ندارد.');
                }

                if ($category->products()->exists()) {
                    throw new BusinessException('امکان حذف دسته‌بندی دارای محصول وجود ندارد.');
                }

                $category->delete();
            });

        }
    }
