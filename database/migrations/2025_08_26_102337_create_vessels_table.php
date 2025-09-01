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
    Schema::create('vessels', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique();      // e.g., "TMC Evolution"
        $table->foreignId('default_port_id')->nullable()
              ->constrained('ports')->nullOnDelete();
        $table->string('contact_name')->nullable();
        $table->string('contact_phone')->nullable();
        $table->string('contact_email')->nullable();
        $table->boolean('active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessels');
    }
};
