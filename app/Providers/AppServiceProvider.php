<?php

    namespace App\Providers;

    use App\Contracts\CodeGeneratorInterface;
    use App\Services\CodeGeneratorService;
    use Illuminate\Http\Resources\Json\JsonResource;
    use App\Models\ScientificVisitorInventory;
    use App\Policies\ScientificVisitorInventoryPolicy;
    use Illuminate\Support\ServiceProvider;

    class AppServiceProvider extends ServiceProvider {
        /**
         * Register any application services.
         */
        public function register(): void {
            $this->app->singleton(CodeGeneratorInterface::class, CodeGeneratorService::class);
        }

        /**
         * Bootstrap any application services.
         */
        public function boot(): void {
            \Illuminate\Support\Facades\Gate::policy(ScientificVisitorInventory::class, ScientificVisitorInventoryPolicy::class);
            JsonResource::withoutWrapping();
        }
    }
