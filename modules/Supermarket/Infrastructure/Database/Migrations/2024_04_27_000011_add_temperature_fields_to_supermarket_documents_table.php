<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supermarket_documents', function (Blueprint $table) {
            // Temperature monitoring fields
            $table->foreignId('temperature_requirement_id')->nullable()->after('product_id')->constrained('supermarket_product_temperature_requirements')->onDelete('set null');
            $table->boolean('requires_temperature_compliance')->default(false)->after('temperature_requirement_id');
            $table->integer('temperature_violation_count')->default(0)->after('requires_temperature_compliance');
            $table->timestamp('last_temperature_check_at')->nullable()->after('temperature_violation_count');
            $table->string('temperature_compliance_status')->default('unknown')->after('last_temperature_check_at');
            
            // Add index for temperature compliance queries
            $table->index('temperature_compliance_status');
            $table->index('requires_temperature_compliance');
        });
    }

    public function down(): void
    {
        Schema::table('supermarket_documents', function (Blueprint $table) {
            $table->dropForeign(['temperature_requirement_id']);
            $table->dropIndex(['temperature_compliance_status']);
            $table->dropIndex(['requires_temperature_compliance']);
            $table->dropColumn([
                'temperature_requirement_id',
                'requires_temperature_compliance',
                'temperature_violation_count',
                'last_temperature_check_at',
                'temperature_compliance_status',
            ]);
        });
    }
};
