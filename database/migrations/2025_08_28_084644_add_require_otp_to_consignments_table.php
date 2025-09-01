<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('consignments', function (Blueprint $table) {
            if (!Schema::hasColumn('consignments', 'require_otp')) {
                // place near status; adjust "after" to an existing column in your table
                $table->boolean('require_otp')->default(false)->after('status');
            }
        });
    }

    public function down(): void {
        Schema::table('consignments', function (Blueprint $table) {
            if (Schema::hasColumn('consignments', 'require_otp')) {
                $table->dropColumn('require_otp');
            }
        });
    }
};
