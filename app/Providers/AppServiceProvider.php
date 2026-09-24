<?php

namespace App\Providers;

use App\Contracts\CodeGeneratorInterface;
use App\Models\Expense;
use App\Models\ScientificVisitorInventory;
use App\Policies\ExpensePolicy;
use App\Policies\ScientificVisitorInventoryPolicy;
use App\Services\CodeGeneratorService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CodeGeneratorInterface::class, CodeGeneratorService::class);
    }

    public function boot(): void
    {
        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(ScientificVisitorInventory::class, ScientificVisitorInventoryPolicy::class);

        JsonResource::withoutWrapping();
    }
}
