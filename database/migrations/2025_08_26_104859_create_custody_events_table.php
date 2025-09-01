<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('custody_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('consignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // actor (driver/manager)

            $table->string('type', 32); // load | deliver | collect_return | return_to_office | exception

            // Timing & place
            $table->timestamp('occurred_at')->useCurrent();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // Receiver proof (one of OTP or signature)
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone', 32)->nullable();
            $table->string('otp_used', 12)->nullable();
            $table->string('signature_path')->nullable();

            // Extra evidence
            $table->json('photos_json')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['consignment_id', 'type']);
            $table->index(['occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custody_events');
    }
};
