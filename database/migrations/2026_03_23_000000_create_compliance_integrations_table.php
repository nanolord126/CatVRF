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
        if (Schema::hasTable('compliance_integrations')) {
            return;
        }

        Schema::create('compliance_integrations', function (Blueprint $table) {
            $table->id()->comment('Unique ID of the integration');
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete()->comment('Tenant/Seller ID');
            $table->string('type')->comment('Type of integration: honest_sign, mercury, grain, mdlp');
            $table->string('inn', 12)->comment('INN of the seller/tenant');
            $table->text('api_token_encrypted')->comment('Encrypted API token for the service');
            $table->string('status')->default('pending')->comment('Current status: pending, connected, error');
            $table->timestamp('last_checked_at')->nullable()->comment('Last connection test timestamp');
            $table->text('error_message')->nullable()->comment('Error message from the last failed test');
            $table->string('correlation_id', 36)->nullable()->index()->comment('Audit correlation ID');
            $table->timestamps();

            $table->unique(['tenant_id', 'type'], 'tenant_type_unique');
            $table->comment('Regulatory integrations for Honest Sign, Mercury, Grain, etc.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_integrations');
    }
};
