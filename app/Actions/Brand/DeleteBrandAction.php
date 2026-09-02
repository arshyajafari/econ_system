<?php

    namespace App\Actions\Brand;

    use App\Exceptions\BusinessRuleException;
    use App\Models\Brand;
    use Illuminate\Support\Facades\DB;

    class DeleteBrandAction {
        public function execute(Brand $brand): void {
            DB::transaction(function () use ($brand) {
                $brand = Brand::query()->lockForUpdate()->findOrFail($brand->id);

                if ($brand->products()->exists()) {
                    throw new BusinessRuleException('امکان حذف برند دارای محصول وجود ندارد.');
                }

                $brand->delete();
            });
        }
    }
