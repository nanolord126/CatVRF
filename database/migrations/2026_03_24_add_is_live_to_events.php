<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }

        if (!Schema::hasColumn('events', 'is_live')) {
            Schema::table('events', function (Blueprint $table) {
                $table->boolean('is_live')->default(false)->comment('Is event currently live streaming')->after('status');
                $table->index('is_live');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('events', 'is_live')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropIndex(['is_live']);
                $table->dropColumn('is_live');
            });
        }
    }
};
