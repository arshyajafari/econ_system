<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('sequences', function (Blueprint $table) {
                $table->id();
                $table->string('sequence_key')->unique();
                $table->unsignedBigInteger('current_value')->default(0);
                $table->timestampTz('last_generated_at')->nullable();
                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('sequences');
        }
    };
