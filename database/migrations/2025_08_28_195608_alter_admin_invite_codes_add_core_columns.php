<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admin_invite_codes', function (Blueprint $table) {
            // Core fields (only add if missing)
            if (! Schema::hasColumn('admin_invite_codes','code')) {
                $table->string('code', 32)->unique()->after('id');
            }
            if (! Schema::hasColumn('admin_invite_codes','role')) {
                $table->string('role', 50)->index()->after('code'); // 'Super Admin' | 'Logistics Manager'
            }
            if (! Schema::hasColumn('admin_invite_codes','issued_by')) {
                $table->unsignedBigInteger('issued_by')->nullable()->index()->after('role');
            }
            if (! Schema::hasColumn('admin_invite_codes','expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('issued_by');
            }
            if (! Schema::hasColumn('admin_invite_codes','max_uses')) {
                $table->unsignedInteger('max_uses')->nullable()->default(1)->after('expires_at');
            }
            if (! Schema::hasColumn('admin_invite_codes','uses')) {
                $table->unsignedInteger('uses')->default(0)->after('max_uses');
            }
            if (! Schema::hasColumn('admin_invite_codes','is_revoked')) {
                $table->boolean('is_revoked')->default(false)->after('uses');
            }
            if (! Schema::hasColumn('admin_invite_codes','notes')) {
                $table->string('notes', 255)->nullable()->after('is_revoked');
            }
            if (! Schema::hasColumn('admin_invite_codes','last_used_by')) {
                $table->unsignedBigInteger('last_used_by')->nullable()->index()->after('notes');
            }
            if (! Schema::hasColumn('admin_invite_codes','last_used_at')) {
                $table->timestamp('last_used_at')->nullable()->after('last_used_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_invite_codes', function (Blueprint $table) {
            // Safe rollback (drop only if present)
            foreach ([
                'last_used_at','last_used_by','notes','is_revoked','uses',
                'max_uses','expires_at','issued_by','role','code'
            ] as $col) {
                if (Schema::hasColumn('admin_invite_codes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
