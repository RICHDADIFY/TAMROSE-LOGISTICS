<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Make column nullable (works without doctrine/dbal using raw SQL)
        DB::statement("ALTER TABLE trip_requests MODIFY request_key VARCHAR(80) NULL");

        // Convert empty strings to NULL so a unique index can be created
        DB::table('trip_requests')->where('request_key', '')->update(['request_key' => null]);
    }

    public function down(): void
    {
        // Revert (back to NOT NULL, and replace NULLs with empty string)
        DB::table('trip_requests')->whereNull('request_key')->update(['request_key' => '']);
        DB::statement("ALTER TABLE trip_requests MODIFY request_key VARCHAR(80) NOT NULL");
    }
};
