<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trip_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();   // requester
        $table->string('direction');                  // to_onne | to_office
        $table->string('from_location');              // "Office" / "Onne"
        $table->string('to_location');
        $table->dateTime('desired_departure');
        $table->dateTime('desired_return')->nullable();
        $table->unsignedTinyInteger('passengers')->default(1);
        $table->text('purpose')->nullable();
        $table->string('status')->default('pending'); // pending|approved|rejected|completed
        $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_requests');
    }
};
