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
            Schema::create('trip_request_trip', function (Blueprint $table) {
        $table->id();
        $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
        $table->foreignId('trip_request_id')->constrained()->cascadeOnDelete();
        $table->unsignedTinyInteger('allocated_seats')->default(1);
        $table->timestamps();
        $table->unique(['trip_id','trip_request_id']);
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_request_trip');
    }
};
