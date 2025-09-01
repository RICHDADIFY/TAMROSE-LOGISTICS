<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consignment_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('consignment_id')->constrained()->cascadeOnDelete();

            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('unit', 24)->nullable();    // pcs, box, bag, etc.

            $table->json('photos_json')->nullable();   // small array of image paths
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['consignment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignment_items');
    }
};
