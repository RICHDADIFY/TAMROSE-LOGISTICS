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
        Schema::table('consignments', function (Blueprint $table) {
            // Add columns without relying on a specific existing column order
            if (!Schema::hasColumn('consignments', 'delivery_otp')) {
                $table->string('delivery_otp', 12)->nullable();
            }

            if (!Schema::hasColumn('consignments', 'otp_expires_at')) {
                $table->timestamp('otp_expires_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consignments', function (Blueprint $table) {
            if (Schema::hasColumn('consignments', 'otp_expires_at')) {
                $table->dropColumn('otp_expires_at');
            }
            if (Schema::hasColumn('consignments', 'delivery_otp')) {
                $table->dropColumn('delivery_otp');
            }
        });
    }
};
