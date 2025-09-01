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
        Schema::create('trips', function (Blueprint $table) {
        $table->id();
        $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
        $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
        $table->string('direction');               // to_onne | to_office
        $table->dateTime('depart_at');
        $table->dateTime('return_at')->nullable();
        $table->string('status')->default('scheduled'); // scheduled|en_route|returned|cancelled
        $table->text('notes')->nullable();
        $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
