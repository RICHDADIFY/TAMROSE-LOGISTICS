<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Phone (nullable); small index for quick lookups
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 32)->nullable()->after('email');
                $table->index('phone', 'users_phone_index');
            }

            // Profile photo path (public disk)
            if (! Schema::hasColumn('users', 'profile_photo_path')) {
                $table->string('profile_photo_path', 2048)->nullable()->after('remember_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropIndex('users_phone_index');
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('users', 'profile_photo_path')) {
                $table->dropColumn('profile_photo_path');
            }
        });
    }
};
