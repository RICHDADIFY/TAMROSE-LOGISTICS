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
        Schema::create('vehicles', function (Blueprint $table) {
        $table->id();
        $table->string('label');                 // e.g., "Hilux 2", "Bus A"
        $table->string('type')->index();         // Bus | Hilux | Car
        $table->string('plate_number')->unique();
        $table->unsignedSmallInteger('capacity')->default(4);
        $table->boolean('active')->default(true);
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
