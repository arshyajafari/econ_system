<?php

    namespace Database\Factories;

    use App\Models\Brand;
    use App\Models\Product;
    use App\Enums\ProductUnit;
    use App\Models\ProductCategory;
    use Illuminate\Database\Eloquent\Factories\Factory;

    class ProductFactory
        extends Factory
    {
        private const TITLES = [
            'Aspirin',
            'Vitamin C',
            'Amoxicillin',
            'Ibuprofen',
            'Diclofenac',
        ];
        protected $model = Product::class;

        public function definition(): array
        {
            return [
                'brand_id' => Brand::factory(),
                'product_category_id' => ProductCategory::factory(),
                'title' => self::TITLES[array_rand(self::TITLES)],
                'barcode' => null,
                'unit' => ProductUnit::BOX,
                'display_order' => 1,
                'is_sample' => false,
                'is_active' => true,
                'description' => null,
                'meta' => [],
            ];
        }
    }
