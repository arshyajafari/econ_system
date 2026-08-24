<?php

    namespace App\Actions\ProductCategory;

    use App\Actions\BaseAction;
    use App\Models\ProductCategory;
    use App\Services\CodeGeneratorService;
    use Illuminate\Support\Facades\DB;

    class CreateProductCategoryAction extends BaseAction {
        public function __construct(protected CodeGeneratorService $codeGenerator) {
        }

        public function execute(array $data): ProductCategory {
            return DB::transaction(function () use ($data) {
                $data['code'] = $this->codeGenerator->generate(ProductCategory::class);
                $category = new ProductCategory();
                $category->fill($data);
                $category->save();

                return $category->fresh(ProductCategory::DEFAULT_RELATIONS);
            });
        }
    }
