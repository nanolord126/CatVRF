<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-Tenant: Production Hardening
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. User tables - correlation_id + soft_delete
        $users = ['users', 'user_interests', 'user_cohort_assignments'];
        foreach ($users as $tname) {
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

        // 2. Content tables - correlation_id + soft_delete
        $content = ['hotels', 'beauty_salons', 'restaurants', 'services', 'products', 'bookings', 'orders'];
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

        // 3. Interest tables - correlation_id
        $interests = ['user_interest_snapshots', 'recommendation_feedback', 'ml_recommendation_logs'];
        foreach ($interests as $tname) {
            if (Schema::hasTable($tname) && !Schema::hasColumn($tname, 'correlation_id')) {
                Schema::table($tname, function (Blueprint $t) {
                    $t->string('correlation_id')->nullable()->after('id')->index();
                });
            }
        }

        // 4. Add indices for common queries
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $t) {
                if (Schema::hasColumn('users', 'email') && !Schema::hasIndex('users', 'users_email_index')) {
                    $t->index('email');
                }
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $t) {
                if (Schema::hasColumn('bookings', 'user_id') && !Schema::hasIndex('bookings', 'bookings_user_id_index')) {
                    $t->index('user_id');
                }
                if (Schema::hasColumn('bookings', 'status') && !Schema::hasIndex('bookings', 'bookings_status_index')) {
                    $t->index('status');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $t) {
                if (Schema::hasColumn('orders', 'user_id') && !Schema::hasIndex('orders', 'orders_user_id_index')) {
                    $t->index('user_id');
                }
                if (Schema::hasColumn('orders', 'status') && !Schema::hasIndex('orders', 'orders_status_index')) {
                    $t->index('status');
                }
            });
        }

        if (Schema::hasTable('user_interests')) {
            Schema::table('user_interests', function (Blueprint $t) {
                if (Schema::hasColumn('user_interests', 'user_id') && 
                    Schema::hasColumn('user_interests', 'vertical') && 
                    !Schema::hasIndex('user_interests', 'user_interests_user_vertical_index')) {
                    $t->index(['user_id', 'vertical']);
                }
            });
        }

        // 5. Geo indices
        if (Schema::hasTable('geo_zones') && Schema::hasColumn('geo_zones', 'coordinates')) {
            Schema::table('geo_zones', function (Blueprint $t) {
                if (!Schema::hasIndex('geo_zones', 'geo_zones_name_index')) {
                    $t->index('name');
                }
            });
        }

        if (Schema::hasTable('geo_events')) {
            Schema::table('geo_events', function (Blueprint $t) {
                if (Schema::hasColumn('geo_events', 'latitude') && 
                    Schema::hasColumn('geo_events', 'longitude') &&
                    !Schema::hasIndex('geo_events', 'geo_events_lat_lng_index')) {
                    $t->index(['latitude', 'longitude']);
                }
            });
        }
    }

    public function down(): void
    {
        // Safe: не удаляем при откате
    }
};
