<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Consignments: toggle OTP + tunables (safe defaults)
        Schema::table('consignments', function (Blueprint $table) {
            if (!Schema::hasColumn('consignments', 'require_otp')) {
                $table->boolean('require_otp')->default(false)->after('status');
            }
            if (!Schema::hasColumn('consignments', 'otp_length')) {
                $table->unsignedTinyInteger('otp_length')->default(4)->after('require_otp');
            }
            if (!Schema::hasColumn('consignments', 'otp_ttl_minutes')) {
                $table->unsignedSmallInteger('otp_ttl_minutes')->default(30)->after('otp_length');
            }
            if (!Schema::hasColumn('consignments', 'delivery_codeword')) {
                $table->string('delivery_codeword', 32)->nullable()->after('otp_ttl_minutes');
            }
        });

        // Custody events: where we store the signature file path
        Schema::table('custody_events', function (Blueprint $table) {
            if (!Schema::hasColumn('custody_events', 'signature_path')) {
                $table->string('signature_path')->nullable()->after('photos_json');
            }
        });
    }

    public function down(): void
    {
        Schema::table('custody_events', function (Blueprint $table) {
            if (Schema::hasColumn('custody_events', 'signature_path')) {
                $table->dropColumn('signature_path');
            }
        });

        Schema::table('consignments', function (Blueprint $table) {
            foreach (['require_otp','otp_length','otp_ttl_minutes','delivery_codeword'] as $col) {
                if (Schema::hasColumn('consignments', $col)) $table->dropColumn($col);
            }
        });
    }
};
