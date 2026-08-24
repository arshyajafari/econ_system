<?php

    namespace App\Providers;

    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\ServiceProvider;

    class BlueprintMacroServiceProvider extends ServiceProvider {
        public function register(): void {
            //
        }

        public function boot(): void {
            $this->registerPublicId();
            $this->registerAudit();
            $this->registerOffline();
            $this->registerMoney();
            $this->registerStatus();
            $this->registerCommon();
            $this->registerDescription();
            $this->registerSortOrder();
            $this->registerLocation();
            $this->registerAddress();
        }

        private function registerPublicId(): void {
            Blueprint::macro('publicId', function (string $column = 'public_id') {
                /** @var Blueprint $this */

                $this->ulid($column)->unique();
            });
        }

        private function registerAudit(): void {
            Blueprint::macro('audit', function () {
                /** @var Blueprint $this */

                $this->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $this->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $this->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        private function registerOffline(): void {
            Blueprint::macro('offline', function () {
                /** @var Blueprint $this */

                $this->uuid('client_uuid')->nullable()->unique();
                $this->unsignedInteger('sync_version')->default(1);
                $this->timestamp('synced_at')->nullable()->index();
            });
        }

        private function registerMoney(): void {
            Blueprint::macro('money', function (string $column) {
                /** @var Blueprint $this */

                $this->unsignedBigInteger($column)->default(0)->index();
            });
        }

        private function registerStatus(): void {
            Blueprint::macro('status', function (string $default = 'active') {
                /** @var Blueprint $this */

                $this->string('status', 20)->default($default)->index();
            });
        }

        private function registerCommon(): void {
            Blueprint::macro('common', function () {
                /** @var Blueprint $this */

                $this->timestamps();
                $this->softDeletes();
            });
        }

        private function registerDescription(): void {
            Blueprint::macro('description', function (string $column = 'description') {
                /** @var Blueprint $this */

                $this->text($column)->nullable();
            });
        }

        private function registerSortOrder(): void {
            Blueprint::macro('sort', function () {
                /** @var Blueprint $this */

                $this->unsignedInteger('sort_order')->default(0);
            });
        }

        private function registerLocation(): void {
            Blueprint::macro('location', function () {
                /** @var Blueprint $this */

                $this->decimal('latitude', 10, 7)->nullable();
                $this->decimal('longitude', 10, 7)->nullable();
            });
        }

        private function registerAddress(): void {
            Blueprint::macro('address', function () {
                /** @var Blueprint $this */

                $this->string('province', 100);
                $this->string('city', 100);
                $this->string('address', 1000);
                $this->string('postal_code', 20)->nullable();
            });
        }
    }
