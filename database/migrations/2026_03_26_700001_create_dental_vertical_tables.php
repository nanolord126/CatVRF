<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for Dental Vertical - CAR 2026 Production Ready.
 * Covers clinics, dentists, services, appointments, treatments, and AI analysis.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Dental Clinics
        if (!Schema::hasTable('dental_clinics')) {
            Schema::create('dental_clinics', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name');
                $table->string('license_number')->unique();
                $table->text('address');
                $table->jsonb('schedule')->nullable();
                $table->integer('rating')->default(0);
                $table->boolean('is_premium')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Dental clinics core table for tenant-based isolation.');
            });
        }

        // 2. Dentists
        if (!Schema::hasTable('dentists')) {
            Schema::create('dentists', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('clinic_id')->constrained('dental_clinics')->onDelete('cascade');
                $table->string('full_name');
                $table->string('specialization'); // Surgery, Orthodontics, etc.
                $table->integer('experience_years');
                $table->text('bio')->nullable();
                $table->jsonb('certifications')->nullable();
                $table->integer('rating')->default(0);
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. Dental Services
        if (!Schema::hasTable('dental_services')) {
            Schema::create('dental_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('clinic_id')->constrained('dental_clinics')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->integer('base_price'); // In kopecks
                $table->integer('duration_minutes')->default(30);
                $table->jsonb('consumables_required')->nullable();
                $table->string('category')->index(); // Therapy, Surgery, Orthodontics
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 4. Dental Appointments
        if (!Schema::hasTable('dental_appointments')) {
            Schema::create('dental_appointments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('clinic_id')->constrained('dental_clinics');
                $table->foreignId('dentist_id')->constrained('dentists');
                $table->foreignId('client_id')->index(); // Link to generic users
                $table->dateTime('scheduled_at');
                $table->string('status')->default('pending'); // pending, confirmed, completed, cancelled
                $table->integer('total_price')->default(0);
                $table->boolean('is_prepaid')->default(false);
                $table->text('internal_notes')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 5. Treatment Plans
        if (!Schema::hasTable('dental_treatment_plans')) {
            Schema::create('dental_treatment_plans', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('client_id')->index();
                $table->foreignId('dentist_id')->constrained('dentists');
                $table->string('title');
                $table->jsonb('steps')->nullable(); // List of services and order
                $table->integer('estimated_budget');
                $table->string('status')->default('draft'); // draft, active, finished, archived
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 6. Dental Consumables
        if (!Schema::hasTable('dental_consumables')) {
            Schema::create('dental_consumables', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('clinic_id')->constrained('dental_clinics');
                $table->string('name');
                $table->string('sku')->unique();
                $table->integer('current_stock')->default(0);
                $table->integer('min_threshold')->default(10);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 7. Dental Reviews
        if (!Schema::hasTable('dental_reviews')) {
            Schema::create('dental_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('clinic_id')->constrained('dental_clinics');
                $table->foreignId('dentist_id')->constrained('dentists');
                $table->foreignId('client_id')->index();
                $table->integer('rating')->default(100); // 0-100
                $table->text('comment')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_reviews');
        Schema::dropIfExists('dental_consumables');
        Schema::dropIfExists('dental_treatment_plans');
        Schema::dropIfExists('dental_appointments');
        Schema::dropIfExists('dental_services');
        Schema::dropIfExists('dentists');
        Schema::dropIfExists('dental_clinics');
    }
};
