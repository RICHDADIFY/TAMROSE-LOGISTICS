<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $t) {
            // All nullable so existing rows remain valid
            $t->string('make', 60)->nullable()->after('type');     // e.g., Toyota
            $t->string('model', 60)->nullable()->after('make');    // e.g., Hiace
            $t->unsignedSmallInteger('year')->nullable()->after('model'); // e.g., 2018
            $t->text('notes')->nullable()->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $t) {
            $t->dropColumn(['make', 'model', 'year', 'notes']);
        });
    }
};

