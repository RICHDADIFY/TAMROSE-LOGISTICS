<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('trip_requests', function (Blueprint $table) {
            // Coordinates aligned to existing columns:
            $table->decimal('from_lat', 10, 7)->nullable()->after('from_location');
            $table->decimal('from_lng', 10, 7)->nullable()->after('from_lat');
            $table->decimal('to_lat',   10, 7)->nullable()->after('to_location');
            $table->decimal('to_lng',   10, 7)->nullable()->after('to_lat');

            $table->index(['from_lat','from_lng'], 'trip_from_coords_idx');
            $table->index(['to_lat','to_lng'],     'trip_to_coords_idx');
        });
    }

    public function down(): void {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->dropIndex('trip_from_coords_idx');
            $table->dropIndex('trip_to_coords_idx');
            $table->dropColumn(['from_lat','from_lng','to_lat','to_lng']);
        });
    }
};

