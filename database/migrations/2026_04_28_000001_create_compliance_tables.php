<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pii_deletion_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('tenant_id');
            $table->text('reason');
            $table->unsignedBigInteger('requested_by');
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tenant_id']);
            $table->index('status');
        });

        Schema::create('pii_consents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('consent_type'); // data_processing, marketing, analytics
            $table->boolean('granted');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'consent_type']);
            $table->index('tenant_id');
        });

        Schema::create('warehouse_licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('license_type'); // pharmacy, medicine_storage, narcotic, psychotropic, poisonous
            $table->string('license_number');
            $table->date('issued_date');
            $table->date('expiry_date');
            $table->string('issued_by');
            $table->boolean('is_active')->default(true);
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['warehouse_id', 'license_type']);
            $table->index(['tenant_id', 'is_active']);
            $table->index('expiry_date');
        });

        Schema::create('warehouse_storage_zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('category'); // general, cold_chain, freezer, narcotic, psychotropic, poisonous, flammable
            $table->decimal('min_temperature', 5, 2);
            $table->decimal('max_temperature', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['warehouse_id', 'category']);
            $table->index('tenant_id');
        });

        Schema::create('chestnyznak_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('product_id');
            $table->string('gtin');
            $table->string('series');
            $table->string('document_id')->nullable();
            $table->string('status')->default('registered');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['gtin', 'series']);
            $table->index('tenant_id');
        });

        Schema::create('egisz_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('report_type'); // prescription_dispensing, medical_service
            $table->unsignedBigInteger('prescription_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->string('drug_code')->nullable();
            $table->string('service_code')->nullable();
            $table->string('status')->default('pending');
            $table->json('response_data')->nullable();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['report_type', 'tenant_id']);
        });

        Schema::create('egisz_drugs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('drug_code')->unique();
            $table->string('name');
            $table->string('trade_name')->nullable();
            $table->boolean('is_prescription')->default(false);
            $table->boolean('is_narcotic')->default(false);
            $table->boolean('is_psychotropic')->default(false);
            $table->timestamps();

            $table->index('drug_code');
        });

        Schema::create('onec_sync_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sync_type'); // products, inventory, suppliers, financial
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->json('sync_data')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('records_processed')->default(0);
            $table->timestamps();

            $table->index(['sync_type', 'tenant_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onec_sync_logs');
        Schema::dropIfExists('egisz_drugs');
        Schema::dropIfExists('egisz_reports');
        Schema::dropIfExists('chestnyznak_registrations');
        Schema::dropIfExists('warehouse_storage_zones');
        Schema::dropIfExists('warehouse_licenses');
        Schema::dropIfExists('pii_consents');
        Schema::dropIfExists('pii_deletion_requests');
    }
};
