<?php

    use App\Enums\EmploymentType;
    use App\Enums\Gender;
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        public function up(): void {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->string('code', 15)->unique();
                $table->string('first_name', 100);
                $table->string('last_name', 100);
                $table->string('national_code', 25)->unique();
                $table->string('phone_number', 20)->unique();
                $table->string('social_link', 20)->nullable();
                $table->string('email')->nullable()->unique();
                $table->string('gender', 20)->default(Gender::MALE->value);
                $table->date('birth_date')->nullable();
                $table->string('card_number', 50)->nullable();
                $table->string('iban_number', 50)->nullable();
                $table->string('employment_type', 30)->default(EmploymentType::FULL_TIME->value);
                $table->date('hire_date');
                $table->date('termination_date')->nullable();
                $table->status();
                $table->description();
                $table->json('meta')->nullable();
                $table->audit();
                $table->common();

                $table->index('last_name');
                $table->index('national_code');
                $table->index('phone_number');
                $table->index('status');
            });
        }

        public function down(): void {
            Schema::dropIfExists('employees');
        }
    };
