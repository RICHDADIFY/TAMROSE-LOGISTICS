<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consignments', function (Blueprint $table) {
            $table->id();

            // Links
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();  // trips.id
            $table->foreignId('vessel_id')->nullable()->constrained()->nullOnDelete(); // vessels.id
            $table->foreignId('port_id')->nullable()->constrained('ports')->nullOnDelete(); // ports.id

            // Type & status
            $table->string('type', 16)->default('outbound'); // outbound | return
            $table->string('status', 24)->default('pending_load'); 
            // pending_load → loaded → delivered ; (return flow) collected → returned
            // terminal: delivered (outbound), returned (return)

            // Destination fallback (e.g., Guest House / ad-hoc label)
            $table->string('destination_label')->nullable();

            // Contact snapshot (prefilled from vessel/port; editable)
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->string('contact_email')->nullable();

            // OTP (simple MVP)
            $table->string('otp_code', 12)->nullable();
            $table->timestamp('otp_expires_at')->nullable();

            // Evidence (small JSON array of photo paths / notes)
            $table->json('evidence_json')->nullable();

            // Optional link back to an associated consignment (e.g., return ↔ outbound)
            $table->foreignId('related_consignment_id')->nullable()
                  ->constrained('consignments')->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index(['trip_id', 'status']);
            $table->index(['type', 'status']);
            $table->index(['port_id', 'vessel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignments');
    }
};
