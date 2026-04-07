<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Insurance Vertical Tables (Layer 1).
 * Vertical: Insurance (OSAGO, KASKO, Health, Property, Travel).
 * Implementation: 9-LAYER ARCHITECTURE 2026.
 * Metadata: UTF-8, CRLF, Strict Types, Comments, Indexes, CorrelationID, TenantScoped.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Insurance Companies Table
        if (!Schema::hasTable('insurance_companies')) {
            Schema::create('insurance_companies', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index()->comment('Tenant isolation scope');
                $table->string('name')->comment('Company legal name');
                $table->string('inn', 12)->index()->comment('Tax registration number');
                $table->string('license_number')->unique()->comment('Official insurance license');
                $table->decimal('rating', 3, 2)->default(0.00)->comment('ML/User combined rating');
                $table->jsonb('contacts')->nullable()->comment('Phones, emails, physical addresses');
                $table->jsonb('settings')->nullable()->comment('Commission rules, risk multipliers');
                $table->string('status')->default('active')->index()->comment('active, suspended, verification');
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable()->comment('Analytics & segment filtering');
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Companies providing insurance services in the marketplace');
            });
        }

        // 2. Insurance Types Table
        if (!Schema::hasTable('insurance_types')) {
            Schema::create('insurance_types', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('slug')->unique()->index()->comment('osago, kasko, health, etc.');
                $table->string('name')->comment('Display name');
                $table->text('description')->nullable();
                $table->jsonb('base_multipliers')->nullable()->comment('Base risk factors for this insurance type');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Global insurance categories and base risk settings');
            });
        }

        // 3. Insurance Policies Table
        if (!Schema::hasTable('insurance_policies')) {
            Schema::create('insurance_policies', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('company_id')->constrained('insurance_companies');
                $table->foreignId('type_id')->constrained('insurance_types');
                $table->foreignId('user_id')->index()->comment('Policy owner');
                $table->string('policy_number')->unique()->index()->comment('External formal number');
                $table->integer('premium_amount')->comment('Policy cost in cents');
                $table->integer('coverage_amount')->comment('Max payout in cents');
                $table->timestamp('starts_at')->index();
                $table->timestamp('expires_at')->index();
                $table->string('status')->default('pending')->index()->comment('pending, active, expired, cancelled');
                $table->jsonb('policy_data')->nullable()->comment('Vehicle details, health profile, etc.');
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Issued insurance policies with specific coverage and logic');
            });
        }

        // 4. Insurance Claims Table
        if (!Schema::hasTable('insurance_claims')) {
            Schema::create('insurance_claims', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('policy_id')->constrained('insurance_policies');
                $table->string('claim_number')->unique()->index();
                $table->text('description')->comment('Incident description');
                $table->integer('requested_amount')->comment('Requested payout in cents');
                $table->integer('approved_amount')->nullable()->comment('Scientifically verified payout');
                $table->string('status')->default('submitted')->index()->comment('submitted, investigating, approved, rejected, paid');
                $table->jsonb('evidence_files')->nullable()->comment('Photos, police reports, receipts');
                $table->jsonb('fraud_score')->nullable()->comment('ML fraud detection results');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Insurance payout requests and their investigation status');
            });
        }

        // 5. Insurance Contracts Table
        if (!Schema::hasTable('insurance_contracts')) {
            Schema::create('insurance_contracts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('policy_id')->constrained('insurance_policies');
                $table->string('document_url')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->jsonb('digital_signature')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Legal documents and digital signatures for policies');
            });
        }

        // 6. Insurance Reviews Table
        if (!Schema::hasTable('insurance_reviews')) {
            Schema::create('insurance_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('company_id')->constrained('insurance_companies');
                $table->foreignId('user_id')->index();
                $table->integer('rating')->unsigned();
                $table->text('comment')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['company_id', 'rating']);
                $table->comment('User feedback for insurance companies');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_reviews');
        Schema::dropIfExists('insurance_contracts');
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('insurance_policies');
        Schema::dropIfExists('insurance_types');
        Schema::dropIfExists('insurance_companies');
    }
};


