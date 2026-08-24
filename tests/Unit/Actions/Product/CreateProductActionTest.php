<?php

    namespace Tests\Unit\Actions\Product;

    use Tests\TestCase;
    use App\Models\Brand;
    use App\Models\Product;
    use App\Models\ProductCategory;
    use App\Actions\Product\CreateProductAction;
    use Illuminate\Foundation\Testing\RefreshDatabase;

    class CreateProductActionTest
        extends TestCase
    {
        use RefreshDatabase;

        public function test_it_can_create_product(): void
        {
            $brand = Brand::factory()->create();

            $category = ProductCategory::factory()->create();

            $action = app(CreateProductAction::class);

            $product = $action->execute([
                'brand_id' => $brand->id,
                'product_category_id' => $category->id,
                'title' => 'Aspirin',
                'unit' => 'box',
                'is_active' => true,
                'is_sample' => false,
            ]);

            $this->assertInstanceOf(Product::class, $product);

            $this->assertDatabaseHas('products', [
                'id' => $product->id,
                'title' => 'Aspirin',
            ]);
        }
    }
