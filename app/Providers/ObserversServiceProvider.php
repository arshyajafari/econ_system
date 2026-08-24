<?php

    namespace App\Providers;

    use App\Models\Brand;
    use App\Models\Product;
    use App\Models\ProductCategory;
    use App\Observers\BrandObserver;
    use App\Observers\ProductCategoryObserver;
    use App\Observers\ProductObserver;
    use Illuminate\Support\ServiceProvider;

    class ObserversServiceProvider extends ServiceProvider {
        /**
         * Register any application services.
         */
        public function register(): void {
            //
        }

        /**
         * Bootstrap any application services.
         */
        public function boot(): void {
            Brand::observe(BrandObserver::class);
            Product::observe(ProductObserver::class);
            ProductCategory::observe(ProductCategoryObserver::class);
        }
    }
