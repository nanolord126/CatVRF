<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Добавляет UUID поля к таблицам wallets и transactions для публичного API.
     * Production 2026: idempotent, обратная совместимость.
     */
    public function up(): void
    {
        if (Schema::hasTable('wallets') && !Schema::hasColumn('wallets', 'uuid')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->unique()->after('id')->comment('UUID для публичного API');
            });
        }
        
        if (Schema::hasTable('transactions') && !Schema::hasColumn('transactions', 'uuid')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->unique()->after('id')->comment('UUID для публичного API');
            });
        }
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            if (Schema::hasColumn('wallets', 'uuid')) {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            }
        });
        
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'uuid')) {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            }
        });
    }
};
