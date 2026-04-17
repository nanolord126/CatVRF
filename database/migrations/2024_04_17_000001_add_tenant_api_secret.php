<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Add api_secret column to tenants table for signature validation
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('api_secret', 64)->nullable()->after('verification_code');
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
            $table->timestamp('retention_until')->nullable()->after('deactivated_at');
        });

        // Generate secrets for existing tenants
        \Illuminate\Support\Facades\DB::table('tenants')
            ->whereNull('api_secret')
            ->get()
            ->each(function ($tenant) {
                $secret = hash('sha256', $tenant->id . config('app.key') . now()->timestamp);
                \Illuminate\Support\Facades\DB::table('tenants')
                    ->where('id', $tenant->id)
                    ->update(['api_secret' => $secret]);
            });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['api_secret', 'deactivated_at', 'retention_until']);
        });
    }
};
