<?php

    use App\Enums\CustomerType;
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->string('code', 30)->unique();
                $table->string('customer_name', 300);
                $table->string('type', 30)->default(CustomerType::PHARMACY->value)->index();
                $table->string('owner_name', 120)->nullable();
                $table->string('manager_name', 120)->nullable();
                $table->string('economic_code', 25)->nullable();
                $table->string('national_code', 25)->nullable();
                $table->string('phone_number', 20)->nullable();
                $table->string('telephone_number', 20)->nullable();
                $table->string('social_link')->nullable();
                $table->date('birth_date')->nullable();
                $table->status();
                $table->string('attachment', 500)->nullable();
                $table->description();
                $table->json('meta')->nullable();
                $table->audit();
                $table->common();

                $table->index('customer_name');
                $table->index('phone_number');
                $table->index('status');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('customers');
        }
    };
