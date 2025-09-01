<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// database/migrations/xxxx_xx_xx_xxxxxx_add_request_key_to_trip_requests.php
return new class extends Migration {
    public function up(): void {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->string('request_key', 80)->after('user_id');
            $table->unique('request_key', 'trip_requests_request_key_unique');
        });
    }
    public function down(): void {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->dropUnique('trip_requests_request_key_unique');
            $table->dropColumn('request_key');
        });
    }
};
