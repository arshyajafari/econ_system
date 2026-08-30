<?php

    namespace App\Actions\Product;

    use App\Exceptions\BusinessRuleException;
    use App\Models\Product;
    use Illuminate\Support\Facades\DB;

    class DeleteProductAction {
        public function execute(Product $product): void {
            DB::transaction(function () use ($product) {
                if (!$product->canBeDeleted()) {
                    throw new BusinessRuleException('این محصول دارای سابقه عملیاتی است و امکان حذف آن وجود ندارد.');
                }

                $product->delete();
            });
        }
    }
