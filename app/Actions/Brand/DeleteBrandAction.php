<?php

    namespace App\Actions\Brand;

    use App\Exceptions\BusinessException;
    use App\Models\Brand;
    use Illuminate\Support\Facades\DB;

    class DeleteBrandAction {
        public function execute(Brand $brand): void {
            DB::transaction(function () use ($brand) {
                if ($brand->products()->exists()) {
                    throw new BusinessException('امکان حذف برند دارای محصول وجود ندارد.');
                }

                $brand->delete();
            });
        }
    }
