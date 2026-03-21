<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenants', 'uuid')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            });
        }

        if (! Schema::hasColumn('tenants', 'slug')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('slug')->nullable()->unique()->after('uuid');
            });
        }

        if (! Schema::hasColumn('tenants', 'correlation_id')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('correlation_id')->nullable()->index()->after('slug');
            });
        }

        if (! Schema::hasColumn('tenants', 'business_group_id')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->unsignedBigInteger('business_group_id')->nullable()->after('correlation_id');
            });
        }

        // Add business entity fields
        if (! Schema::hasColumn('tenants', 'inn')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('inn')->nullable()->unique()->after('business_group_id');
                $table->string('kpp')->nullable()->after('inn');
                $table->string('ogrn')->nullable()->unique()->after('kpp');
                $table->string('legal_entity_type')->nullable()->after('ogrn');
                $table->text('legal_address')->nullable()->after('legal_entity_type');
                $table->text('actual_address')->nullable()->after('legal_address');
                $table->string('phone')->nullable()->after('actual_address');
                $table->string('email')->nullable()->after('phone');
                $table->string('website')->nullable()->after('email');
                $table->boolean('is_active')->default(true)->after('website');
                $table->boolean('is_verified')->default(false)->after('is_active');
                $table->json('meta')->nullable()->after('is_verified');
                $table->json('tags')->nullable()->after('meta');
            });
        }

        // Populate uuid for existing tenants if missing
        if (Schema::hasColumn('tenants', 'uuid')) {
            $tenants = Schema::getConnection()->table('tenants')->whereNull('uuid')->get();
            foreach ($tenants as $tenant) {
                Schema::getConnection()->table('tenants')
                    ->where('id', $tenant->id)
                    ->update(['uuid' => Str::uuid()]);
            }
        }
    }

    public function down(): void
    {
        // Only drop columns if they exist - SQLite constraints issues
        Schema::disableForeignKeyConstraints();
        Schema::table('tenants', function (Blueprint $table) {
            $columns = Schema::getColumnListing('tenants');
            
            if (in_array('tags', $columns)) {
                $table->dropColumn('tags');
            }
            if (in_array('meta', $columns)) {
                $table->dropColumn('meta');
            }
            if (in_array('is_verified', $columns)) {
                $table->dropColumn('is_verified');
            }
            if (in_array('is_active', $columns)) {
                $table->dropColumn('is_active');
            }
            if (in_array('website', $columns)) {
                $table->dropColumn('website');
            }
            if (in_array('email', $columns)) {
                $table->dropColumn('email');
            }
            if (in_array('phone', $columns)) {
                $table->dropColumn('phone');
            }
            if (in_array('actual_address', $columns)) {
                $table->dropColumn('actual_address');
            }
            if (in_array('legal_address', $columns)) {
                $table->dropColumn('legal_address');
            }
            if (in_array('legal_entity_type', $columns)) {
                $table->dropColumn('legal_entity_type');
            }
            if (in_array('ogrn', $columns)) {
                $table->dropColumn('ogrn');
            }
            if (in_array('kpp', $columns)) {
                $table->dropColumn('kpp');
            }
            if (in_array('inn', $columns)) {
                $table->dropColumn('inn');
            }
            if (in_array('business_group_id', $columns)) {
                $table->dropColumn('business_group_id');
            }
            if (in_array('correlation_id', $columns)) {
                $table->dropColumn('correlation_id');
            }
            if (in_array('slug', $columns)) {
                $table->dropColumn('slug');
            }
            if (in_array('uuid', $columns)) {
                $table->dropColumn('uuid');
            }
        });
        Schema::enableForeignKeyConstraints();
    }
};
