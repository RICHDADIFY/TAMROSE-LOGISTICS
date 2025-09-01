<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $table = 'driver_locations';
    private string $index = 'driver_locations_trip_ts_idx';

    public function up(): void
    {
        if (! $this->indexExists($this->table, $this->index)) {
            Schema::table($this->table, function (Blueprint $t) {
                // Speeds up: WHERE trip_id = ? ORDER BY recorded_at
                $t->index(['trip_id', 'recorded_at'], $this->index);
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists($this->table, $this->index)) {
            Schema::table($this->table, function (Blueprint $t) {
                $t->dropIndex($this->index);
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        // Works on MySQL/MariaDB
        $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
        return !empty($rows);
    }
};
