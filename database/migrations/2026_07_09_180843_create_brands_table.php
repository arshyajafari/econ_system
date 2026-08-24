<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('brands', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->string('code', 30)->unique();
                $table->string('title', 150);
                $table->string('logo', 500)->nullable();
                $table->description();
                $table->sort();
                $table->boolean('is_active')->default(true);
                $table->json('meta')->nullable();
                $table->audit();
                $table->common();

                $table->index('title');
                $table->index('is_active');
                $table->index('sort_order');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('brands');
        }
    };
