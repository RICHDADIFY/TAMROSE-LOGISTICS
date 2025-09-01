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
        Schema::create('driver_locations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('trip_id');
            $table->unsignedBigInteger('driver_id');

            $table->decimal('lat', 10, 7);    // latitude ~1cm precision
            $table->decimal('lng', 10, 7);    // longitude
            $table->unsignedSmallInteger('heading')->nullable(); // degrees 0–359
            $table->decimal('speed', 6, 2)->nullable(); // km/h

            $table->timestamp('recorded_at'); // when the ping was recorded
            $table->timestamps();             // created_at & updated_at

            // Indexes for faster queries
            $table->index(['trip_id', 'recorded_at']);
            $table->index(['driver_id', 'recorded_at']);

            // Prevent duplicate pings at the same second
            $table->unique(['trip_id', 'driver_id', 'recorded_at'], 'unique_trip_driver_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_locations');
    }
};
