<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('trip_requests', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('trip_requests', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            if (Schema::hasColumn('trip_requests', 'approved_by')) {
                // If you later added an FK manually, drop it first:
                // try { $table->dropForeign(['approved_by']); } catch (\Throwable $e) {}
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('trip_requests', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};
