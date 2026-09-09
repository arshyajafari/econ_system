<?php

    namespace App\Actions\Product;

    use App\Models\Brand;
    use App\Models\Product;
    use App\Models\ProductCategory;
    use App\Services\CodeGeneratorService;
    use Illuminate\Support\Facades\DB;

    class CreateProductAction {
        public function __construct(protected CodeGeneratorService $codeGenerator) {
        }

        public function execute(array $data): Product {
            return DB::transaction(function () use ($data) {
                $data['brand_id'] = Brand::query()->where('public_id', $data['brand_id'])->value('id');

                $data['product_category_id'] = ProductCategory::query()
                    ->where('public_id', $data['product_category_id'])->value('id');

                $data['code'] = $this->codeGenerator->generate(Product::class);

                $product = new Product();
                $product->fill($data);
                $product->save();

                return $product->fresh(Product::DEFAULT_RELATIONS);
            });
        }
    }
