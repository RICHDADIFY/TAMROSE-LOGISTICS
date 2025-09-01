<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add ONLY if the column does not already exist
        if (! Schema::hasColumn('trip_requests', 'status')) {
            Schema::table('trip_requests', function (Blueprint $table) {
                $table->string('status')->default('pending')->after('user_id');
            });
        }

        // If this migration also added other columns before,
        // guard them the same way, e.g.:
        // if (! Schema::hasColumn('trip_requests', 'approved_at')) {
        //     Schema::table('trip_requests', function (Blueprint $table) {
        //         $table->timestamp('approved_at')->nullable()->after('status');
        //     });
        // }
        // if (! Schema::hasColumn('trip_requests', 'rejected_at')) {
        //     Schema::table('trip_requests', function (Blueprint $table) {
        //         $table->timestamp('rejected_at')->nullable()->after('approved_at');
        //     });
        // }
    }

    public function down(): void
    {
        if (Schema::hasColumn('trip_requests', 'status')) {
            Schema::table('trip_requests', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        // if (Schema::hasColumn('trip_requests', 'approved_at')) {
        //     Schema::table('trip_requests', function (Blueprint $table) {
        //         $table->dropColumn('approved_at');
        //     });
        // }
        // if (Schema::hasColumn('trip_requests', 'rejected_at')) {
        //     Schema::table('trip_requests', function (Blueprint $table) {
        //         $table->dropColumn('rejected_at');
        //     });
        // }
    }
};
