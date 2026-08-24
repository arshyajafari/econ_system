<?php

    namespace App\Providers;

    use App\Contracts\CodeGeneratorInterface;
    use App\Services\CodeGeneratorService;
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
            //
        }
    }
