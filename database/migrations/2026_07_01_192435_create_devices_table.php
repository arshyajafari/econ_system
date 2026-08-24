<?php

    use App\Enums\DevicePlatform;
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('devices', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
                $table->uuid('device_id')->unique();
                $table->string('device_label', 100)->nullable();
                $table->string('platform', 20)->default(DevicePlatform::WEB->value);
                $table->string('platform_version', 50)->nullable();
                $table->string('app_version', 20)->nullable();
                $table->text('push_token')->nullable();
                $table->timestamp('last_seen_at')->nullable()->index();
                $table->timestamp('last_sync_at')->nullable();
                $table->ipAddress('last_ip')->nullable();
                $table->status();
                $table->json('meta')->nullable();
                $table->common();
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('devices');
        }
    };
