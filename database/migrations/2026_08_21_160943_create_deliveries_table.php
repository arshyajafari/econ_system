<?php

    use App\Enums\DeliveryStatus;
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('deliveries', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->foreignId('order_id')->constrained('orders')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->restrictOnDelete()
                    ->cascadeOnUpdate();
                $table->status()->default(DeliveryStatus::PENDING->value);
                $table->timestamp('prepared_at')->nullable();
                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('recipient_name', 150);
                $table->string('recipient_phone', 30);
                $table->text('address');
                $table->description();
                $table->json('meta')->nullable();
                $table->audit();
                $table->common();

                $table->index('customer_id');
                $table->index('employee_id');
                $table->index('status');
                $table->index('delivered_at');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('deliveries');
        }
    };
