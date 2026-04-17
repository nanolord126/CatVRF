<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_addresses')) {
            return;
        }

        Schema::table('user_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('user_addresses', 'tenant_id')) {
                $table->foreignId('tenant_id')->after('id')->constrained()->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('user_addresses', 'uuid')) {
                $table->uuid('uuid')->after('id')->unique();
            }
            
            if (!Schema::hasColumn('user_addresses', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            
            if (!Schema::hasColumn('user_addresses', 'region')) {
                $table->string('region')->nullable()->after('city');
            }
            
            if (!Schema::hasColumn('user_addresses', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('region');
            }
            
            if (!Schema::hasColumn('user_addresses', 'country')) {
                $table->string('country')->nullable()->after('postal_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'uuid', 'city', 'region', 'postal_code', 'country']);
        });
    }
};
