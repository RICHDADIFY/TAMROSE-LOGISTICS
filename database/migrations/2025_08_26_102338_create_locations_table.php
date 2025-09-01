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
    Schema::create('locations', function (Blueprint $table) {
        $table->id();
        $table->string('name');                         // Office, Onne, Guest House
        $table->string('type', 32);                     // office | onne_cluster | guest_house | other
        $table->string('address')->nullable();
        $table->decimal('lat', 10, 7)->nullable();
        $table->decimal('lng', 10, 7)->nullable();
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
        Schema::dropIfExists('locations');
    }
};
