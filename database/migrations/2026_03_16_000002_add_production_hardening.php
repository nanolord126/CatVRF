<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026 Production Hardening: Add correlation_id, soft_delete, and indices
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. USERS
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'correlation_id')) {
            Schema::table('users', function (Blueprint $t) {
                $t->uuid('correlation_id')->nullable()->after('id')->index();
            });
        }
        
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $t) {
                $t->softDeletes();
            });
        }

        // 2. Financial tables
        $financial = ['wallets', 'transactions', 'transfers', 'platform_commissions'];
        foreach ($financial as $tname) {
            if (Schema::hasTable($tname) && !Schema::hasColumn($tname, 'correlation_id')) {
                Schema::table($tname, function (Blueprint $t) {
                    $t->string('correlation_id')->nullable()->after('id')->index();
                });
            }
            if (Schema::hasTable($tname) && !Schema::hasColumn($tname, 'deleted_at')) {
                Schema::table($tname, function (Blueprint $t) {
                    $t->softDeletes();
                });
            }
        }

        // 3. Content tables
        $content = ['bookings', 'orders', 'services', 'properties', 'hotels', 'beauty_salons'];
        foreach ($content as $tname) {
            if (Schema::hasTable($tname) && !Schema::hasColumn($tname, 'correlation_id')) {
                Schema::table($tname, function (Blueprint $t) {
                    $t->string('correlation_id')->nullable()->after('id')->index();
                });
            }
            if (Schema::hasTable($tname) && !Schema::hasColumn($tname, 'deleted_at')) {
                Schema::table($tname, function (Blueprint $t) {
                    $t->softDeletes();
                });
            }
        }

        // 4. Audit tables
        $audit = ['action_audits', 'ai_user_telemetry'];
        foreach ($audit as $tname) {
            if (Schema::hasTable($tname) && !Schema::hasColumn($tname, 'correlation_id')) {
                Schema::table($tname, function (Blueprint $t) {
                    $t->string('correlation_id')->nullable()->after('id')->index();
                });
            }
        }

        // 5. Indices on key tables
        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $t) {
                if (!Schema::hasIndex('permissions', 'permissions_name_index')) {
                    $t->index('name');
                }
            });
        }

        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $t) {
                if (!Schema::hasIndex('roles', 'roles_name_index')) {
                    $t->index('name');
                }
            });
        }

        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $t) {
                if (!Schema::hasIndex('tenants', 'tenants_created_at_index')) {
                    $t->index('created_at');
                }
                if (Schema::hasColumn('tenants', 'parent_id') && !Schema::hasIndex('tenants', 'tenants_parent_id_index')) {
                    $t->index('parent_id');
                }
            });
        }

        if (Schema::hasTable('domains')) {
            Schema::table('domains', function (Blueprint $t) {
                if (!Schema::hasIndex('domains', 'domains_domain_index')) {
                    $t->index('domain');
                }
            });
        }

        // 6. Geo indices
        if (Schema::hasTable('geo_zones') && Schema::hasColumn('geo_zones', 'latitude')) {
            Schema::table('geo_zones', function (Blueprint $t) {
                if (!Schema::hasIndex('geo_zones', 'geo_zones_lat_lng_index')) {
                    $t->index(['latitude', 'longitude']);
                }
            });
        }

        if (Schema::hasTable('cache')) {
            Schema::table('cache', function (Blueprint $t) {
                if (!Schema::hasIndex('cache', 'cache_expiration_index')) {
                    $t->index('expiration');
                }
            });
        }
    }

    public function down(): void
    {
        // Safe: не удаляем при откате
    }
};
