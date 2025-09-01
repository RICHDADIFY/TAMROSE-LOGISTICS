<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->unique('request_key', 'trip_requests_request_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->dropUnique('trip_requests_request_key_unique');
        });
    }
};
