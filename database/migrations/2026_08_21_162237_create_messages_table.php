<?php

    use App\Enums\MessageStatus;
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration {
        /**
         * Run the migrations.
         */
        public function up(): void {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->publicId();
                $table->foreignId('sender_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('recipient_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
                $table->string('status', 20)->default(MessageStatus::UNREAD->value);
                $table->string('subject', 200)->nullable();
                $table->text('body');
                $table->timestamp('read_at')->nullable();
                $table->description();
                $table->json('meta')->nullable();
                $table->common();

                $table->index('sender_id');
                $table->index('recipient_id');
                $table->index('status');
                $table->index('read_at');
                $table->index([
                    'recipient_id',
                    'status',
                ]);
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void {
            Schema::dropIfExists('messages');
        }
    };
