<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('geo_activities', function (Blueprint $table) {
            if (!Schema::hasColumn('geo_activities', 'synced_to_ch')) {
                $table->boolean('synced_to_ch')
                    ->default(false)
                    ->after('correlation_id')
                    ->comment('Indicates if event was synced to ClickHouse');
            }

            if (!Schema::hasIndex('geo_activities', 'idx_synced_to_ch')) {
                $table->index('synced_to_ch')
                    ->comment('Index for ClickHouse sync queries');
            }
        });

        Schema::table('click_events', function (Blueprint $table) {
            if (!Schema::hasColumn('click_events', 'synced_to_ch')) {
                $table->boolean('synced_to_ch')
                    ->default(false)
                    ->after('correlation_id')
                    ->comment('Indicates if event was synced to ClickHouse');
            }

            if (!Schema::hasIndex('click_events', 'idx_synced_to_ch')) {
                $table->index('synced_to_ch')
                    ->comment('Index for ClickHouse sync queries');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('geo_activities', function (Blueprint $table) {
            if (Schema::hasColumn('geo_activities', 'synced_to_ch')) {
                $table->dropColumn('synced_to_ch');
            }
            if (Schema::hasIndex('geo_activities', 'idx_synced_to_ch')) {
                $table->dropIndex('idx_synced_to_ch');
            }
        });

        Schema::table('click_events', function (Blueprint $table) {
            if (Schema::hasColumn('click_events', 'synced_to_ch')) {
                $table->dropColumn('synced_to_ch');
            }
            if (Schema::hasIndex('click_events', 'idx_synced_to_ch')) {
                $table->dropIndex('idx_synced_to_ch');
            }
        });
    }
};
