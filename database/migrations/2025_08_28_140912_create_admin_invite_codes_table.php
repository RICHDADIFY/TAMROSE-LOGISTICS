<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admin_invite_codes', function (Blueprint $t) {
            $t->id();
            $t->string('role'); // 'Super Admin' | 'Logistics Manager'
            $t->string('label')->nullable();
            $t->string('code_hash'); // hashed code
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->integer('max_uses')->default(1);
            $t->integer('used_count')->default(0);
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();

            $t->index(['role']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('admin_invite_codes');
    }
};
