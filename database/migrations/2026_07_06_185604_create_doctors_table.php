<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('doctors', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->string('code', 30)->unique();
                $table->string('first_name', 100);
                $table->string('last_name', 100);
                $table->string('specialty', 50)->nullable();
                $table->string('phone_number', 20)->nullable();
                $table->string('clinic_name', 100)->nullable();
                $table->string('attachment', 500)->nullable();
                $table->status();
                $table->timestamp('last_visit_at')->nullable();
                $table->boolean('is_favorite')->default(false);
                $table->description();
                $table->json('meta')->nullable();
                $table->audit();
                $table->common();

                $table->index('specialty');
                $table->index('last_name');
                $table->index('phone_number');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('doctors');
        }
    };
