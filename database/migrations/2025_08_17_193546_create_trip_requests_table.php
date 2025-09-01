<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('trip_requests')) {
            Schema::create('trip_requests', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->string('origin', 80);
                $t->string('destination', 80);
                $t->dateTime('desired_time');
                $t->unsignedTinyInteger('passengers')->default(1);
                $t->string('purpose', 180)->nullable();
                $t->enum('status', ['pending','assigned','rejected','completed'])->default('pending');
                // NOTE: don't constrain to 'trips' yet (that table doesn't exist). We'll add FK later.
                $t->unsignedBigInteger('trip_id')->nullable();
                $t->text('manager_note')->nullable();
                $t->timestamps();
                $t->index(['status','desired_time']);
            });
        } else {
            // (Optional) ensure new columns exist if you created the table earlier by hand.
            Schema::table('trip_requests', function (Blueprint $t) {
                if (!Schema::hasColumn('trip_requests','trip_id')) $t->unsignedBigInteger('trip_id')->nullable()->after('status');
                if (!Schema::hasColumn('trip_requests','manager_note')) $t->text('manager_note')->nullable()->after('trip_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_requests');
    }
};

