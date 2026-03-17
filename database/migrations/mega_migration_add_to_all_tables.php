<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Mega-Migration: ALTER all existing tables to add correlation_id, soft_delete, indices
 * 
 * Более безопасный и быстрый способ добавить все нужные колонки
 * ко ВСЕМ существующим таблицам одной миграцией
 */
return new class extends Migration
{
    private array $userTables = ['users', 'user_profiles'];
    private array $financialTables = ['wallets', 'wallet_transactions', 'transfers', 'transactions', 'platform_commissions'];
    private array $contentTables = ['bookings', 'orders', 'services', 'properties', 'hotels', 'beauty_salons', 'restaurants', 'products'];
    private array $auditTables = ['action_audits', 'ai_user_telemetry'];
    private array $geoTables = ['geo_zones', 'geo_events'];

    public function up(): void
    {
        DB::statement('PRAGMA journal_mode = WAL');

        // Add correlation_id everywhere it's missing
        $allTables = array_merge(
            $this->userTables,
            $this->financialTables,
            $this->contentTables,
            $this->auditTables,
            $this->geoTables,
            ['tenants', 'domains', 'permissions', 'roles', 'sessions', 'jobs', 'cache', 'cache_locks']
        );

        foreach ($allTables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'correlation_id')) {
                Schema::table($tableName, function (Blueprint $t) use ($tableName) {
                    // Insert after id if id exists, else at start
                    if (Schema::hasColumn($tableName, 'id')) {
                        $t->string('correlation_id')->nullable()->after('id')->index();
                    } else {
                        $t->string('correlation_id')->nullable()->index();
                    }
                });
            }
        }

        // Add soft_delete to tables that need it
        $softDeleteTables = array_merge($this->userTables, $this->financialTables, $this->contentTables);
        foreach ($softDeleteTables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $t) {
                    $t->softDeletes();
                });
            }
        }

        // Add indices for performance
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $t) {
                if (Schema::hasColumn('users', 'email')) {
                    try {
                        $t->index('email', 'idx_users_email');
                    } catch (\Exception $e) {
                        // Index might already exist
                    }
                }
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $t) {
                if (Schema::hasColumn('bookings', 'user_id')) {
                    try {
                        $t->index('user_id', 'idx_bookings_user_id');
                    } catch (\Exception $e) {}
                }
                if (Schema::hasColumn('bookings', 'status')) {
                    try {
                        $t->index('status', 'idx_bookings_status');
                    } catch (\Exception $e) {}
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $t) {
                if (Schema::hasColumn('orders', 'user_id')) {
                    try {
                        $t->index('user_id', 'idx_orders_user_id');
                    } catch (\Exception $e) {}
                }
                if (Schema::hasColumn('orders', 'status')) {
                    try {
                        $t->index('status', 'idx_orders_status');
                    } catch (\Exception $e) {}
                }
            });
        }

        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $t) {
                try {
                    $t->index('name', 'idx_permissions_name');
                } catch (\Exception $e) {}
            });
        }

        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $t) {
                try {
                    $t->index('name', 'idx_roles_name');
                } catch (\Exception $e) {}
            });
        }

        // Optimize SQLite
        DB::statement('VACUUM');
    }

    public function down(): void
    {
        // Safe: не удаляем при откате
    }
};
