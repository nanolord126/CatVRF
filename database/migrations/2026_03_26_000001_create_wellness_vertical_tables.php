<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for HealthAndWellness vertical.
     * 
     * Architecture: 2026 Production-Ready
     * Tables: wellness_centers, wellness_specialists, wellness_services, 
     *         wellness_appointments, wellness_memberships, wellness_reviews, wellness_programs
     */
    public function up(): void
    {
        // 1. Wellness Centers (Spas, Studios, Clinics)
        if (!Schema::hasTable('wellness_centers')) {
            Schema::create('wellness_centers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->string('business_group_id')->nullable()->index();
                
                $table->string('name')->comment('Legal name of the wellness facility');
                $table->string('type')->comment('spa, yoga_studio, fitness_club, massage_parlor');
                $table->string('address');
                $table->jsonb('geo_point')->nullable();
                $table->jsonb('schedule_json')->comment('Opening hours per day');
                
                $table->decimal('rating', 3, 2)->default(0.00);
                $table->integer('review_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_verified')->default(false);
                
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                
                $table->index(['tenant_id', 'type']);
                $table->comment('Main facilities for Health and Wellness vertical');
            });
        }

        // 2. Specialists (Yoga Instructors, Masseurs, Doctors)
        if (!Schema::hasTable('wellness_specialists')) {
            Schema::create('wellness_specialists', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->foreignId('center_id')->constrained('wellness_centers')->onDelete('cascade');
                
                $table->string('full_name');
                $table->jsonb('qualifications')->comment('Certificates, education, skills');
                $table->integer('experience_years')->default(0);
                $table->string('specialization')->comment('yoga, reiki, deep_tissue, pilates');
                
                $table->decimal('rating', 3, 2)->default(0.00);
                $table->jsonb('medical_compliance')->nullable()->comment('Health certifications for the specialist');
                
                $table->string('correlation_id')->nullable()->index();
                $table->softDeletes();
                $table->timestamps();
                
                $table->comment('Wellness professionals linked to centers');
            });
        }

        // 3. Wellness Services (Massage, Yoga Class, Detox)
        if (!Schema::hasTable('wellness_services')) {
            Schema::create('wellness_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->foreignId('center_id')->constrained('wellness_centers')->onDelete('cascade');
                $table->foreignId('specialist_id')->nullable()->constrained('wellness_specialists')->onDelete('set null');
                
                $table->string('name');
                $table->text('description');
                $table->integer('duration_minutes');
                $table->integer('price')->comment('Price in kopeks');
                $table->string('category')->comment('massage, yoga, meditation, hydrotherapy');
                
                $table->jsonb('medical_restrictions')->nullable()->comment('Contraindications for this service');
                $table->jsonb('consumables_json')->nullable()->comment('Inventory items used for this service');
                
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Catalog of wellness services offered by centers');
            });
        }

        // 4. Appointments (Bookings)
        if (!Schema::hasTable('wellness_appointments')) {
            Schema::create('wellness_appointments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->foreignId('center_id')->constrained('wellness_centers');
                $table->foreignId('service_id')->constrained('wellness_services');
                $table->foreignId('specialist_id')->constrained('wellness_specialists');
                $table->string('user_id')->index()->comment('Client identifier');
                
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->string('status')->default('pending')->comment('pending, confirmed, completed, cancelled');
                $table->integer('final_price')->comment('Price at booking time in kopeks');
                
                $table->jsonb('client_health_notes')->nullable()->comment('Optional health disclosures from client');
                $table->string('payment_status')->default('unpaid');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->index(['tenant_id', 'starts_at', 'status']);
                $table->comment('Client bookings for individual services');
            });
        }

        // 5. Memberships (Subscriptions / Abonnements)
        if (!Schema::hasTable('wellness_memberships')) {
            Schema::create('wellness_memberships', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->string('user_id')->index();
                $table->foreignId('center_id')->constrained('wellness_centers');
                
                $table->string('plan_name')->comment('Silver, Gold, Pro, Family');
                $table->string('type')->comment('unlimited, class_pack, monthly');
                $table->integer('remaining_classes')->nullable();
                $table->integer('price_paid')->comment('Kopeks');
                
                $table->dateTime('starts_at');
                $table->dateTime('expires_at');
                $table->boolean('is_active')->default(true);
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Recurring access plans for wellness centers');
            });
        }

        // 6. Wellness Programs (Multi-day plans generated by AI)
        if (!Schema::hasTable('wellness_programs')) {
            Schema::create('wellness_programs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->string('user_id')->index();
                
                $table->string('goal')->comment('weight_loss, stress_relief, detox');
                $table->jsonb('program_data')->comment('Full roadmap of services and advice');
                $table->float('ai_confidence_score')->default(0.0);
                
                $table->boolean('is_completed')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('AI-generated personalized wellness roadmaps');
            });
        }

        // 7. Reviews
        if (!Schema::hasTable('wellness_reviews')) {
            Schema::create('wellness_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('tenant_id')->index();
                $table->string('user_id')->index();
                
                $table->foreignId('center_id')->nullable()->constrained('wellness_centers');
                $table->foreignId('specialist_id')->nullable()->constrained('wellness_specialists');
                $table->foreignId('appointment_id')->nullable()->constrained('wellness_appointments');
                
                $table->integer('rating')->default(5);
                $table->text('comment');
                $table->jsonb('media_json')->nullable();
                $table->boolean('is_verified')->default(false);
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Customer feedback for wellness services');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wellness_reviews');
        Schema::dropIfExists('wellness_programs');
        Schema::dropIfExists('wellness_memberships');
        Schema::dropIfExists('wellness_appointments');
        Schema::dropIfExists('wellness_services');
        Schema::dropIfExists('wellness_specialists');
        Schema::dropIfExists('wellness_centers');
    }
};
