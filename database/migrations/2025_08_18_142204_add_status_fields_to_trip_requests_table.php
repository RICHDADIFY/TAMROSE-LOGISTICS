<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('trip_requests', 'status')) {
            Schema::table('trip_requests', function (Blueprint $table) {
                $table->string('status', 32)->default('pending')->after('purpose');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('trip_requests', 'status')) {
            Schema::table('trip_requests', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
