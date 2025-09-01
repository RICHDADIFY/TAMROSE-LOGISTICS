<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('status_histories', function (Blueprint $t) {
            $t->id();
            $t->morphs('subject'); // subject_type, subject_id (TripRequest now; Trip later)
            $t->string('from_status')->nullable();
            $t->string('to_status');
            $t->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('note')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('status_histories');
    }
};
