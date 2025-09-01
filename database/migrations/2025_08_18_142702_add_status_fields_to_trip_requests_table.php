<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add columns only if they don't already exist
        if (! Schema::hasColumn('trip_requests', 'status')
            || ! Schema::hasColumn('trip_requests', 'approved_at')
            || ! Schema::hasColumn('trip_requests', 'approved_by')) {

            Schema::table('trip_requests', function (Blueprint $t) {
                if (! Schema::hasColumn('trip_requests', 'status')) {
                    $t->string('status', 32)->default('pending')->index()->after('purpose');
                }
                if (! Schema::hasColumn('trip_requests', 'approved_at')) {
                    $t->timestamp('approved_at')->nullable()->after('status');
                }
                if (! Schema::hasColumn('trip_requests', 'approved_by')) {
                    // requires users table to exist already
                    $t->foreignId('approved_by')->nullable()
                        ->constrained('users')->nullOnDelete()->after('approved_at');
                }
            });
        }
    }

    public function down(): void
    {
        // Drop only what exists (safe rollback)
        Schema::table('trip_requests', function (Blueprint $t) {
            if (Schema::hasColumn('trip_requests', 'approved_by')) {
                // drops FK + column in Laravel 12
                $t->dropConstrainedForeignId('approved_by');
            }
            if (Schema::hasColumn('trip_requests', 'approved_at')) {
                $t->dropColumn('approved_at');
            }
            if (Schema::hasColumn('trip_requests', 'status')) {
                // If you also created an index automatically, dropping the column removes it.
                $t->dropColumn('status');
            }
        });
    }
};
