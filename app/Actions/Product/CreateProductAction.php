<?php

    namespace App\Actions\Product;

    use App\Models\Product;
    use App\Services\CodeGeneratorService;
    use Illuminate\Support\Facades\DB;

    class CreateProductAction {
        public function __construct(protected CodeGeneratorService $codeGenerator) {
        }

        public function execute(array $data): Product {
            return DB::transaction(function () use ($data) {
                $data['code'] = $this->codeGenerator->generate(Product::class);
                $product = new Product();
                $product->fill($data);
                $product->save();

                return $product->fresh(Product::DEFAULT_RELATIONS);
            });
        }
    }
