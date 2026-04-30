<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iot_devices', function (Blueprint $table) {
            // Security status
            $table->enum('security_status', [
                'active',
                'quarantined',
                'compromised',
                'disabled',
            ])->default('active')->after('is_active')->comment('Device security status');
            
            // Quarantine details
            $table->timestamp('quarantined_at')->nullable()->after('security_status');
            $table->text('quarantine_reason')->nullable()->after('quarantined_at');
            $table->foreignId('quarantined_by')->nullable()->constrained('users')->onDelete('set null')->after('quarantine_reason');
            
            // Security metadata
            $table->string('security_level')->default('standard')->after('description')->comment('Security level: standard, high, critical');
            $table->json('security_metadata')->nullable()->after('security_level')->comment('Security-related metadata');
            
            // Rate limiting
            $table->integer('rate_limit_per_minute')->default(60)->after('security_metadata')->comment('Max requests per minute');
            $table->timestamp('rate_limit_reset_at')->nullable()->after('rate_limit_per_minute');
            
            // Last security check
            $table->timestamp('last_security_check_at')->nullable()->after('rate_limit_reset_at');
            $table->string('last_security_check_result')->nullable()->after('last_security_check_at');
            
            // Indexes for security queries
            $table->index(['tenant_id', 'security_status']);
            $table->index(['security_status', 'quarantined_at']);
        });
    }

    public function down(): void
    {
        Schema::table('iot_devices', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'security_status']);
            $table->dropIndex(['security_status', 'quarantined_at']);
            $table->dropColumn([
                'security_status',
                'quarantined_at',
                'quarantine_reason',
                'quarantined_by',
                'security_level',
                'security_metadata',
                'rate_limit_per_minute',
                'rate_limit_reset_at',
                'last_security_check_at',
                'last_security_check_result',
            ]);
        });
    }
};
