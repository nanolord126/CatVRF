<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Consulting vertical.
     * Implementing 9-LAYER ARCHITECTURE 2026.
     * Tables: consulting_firms, consultants, consulting_services, consulting_sessions, consulting_contracts, consulting_reviews, consulting_projects.
     */
    public function up(): void
    {
        if (!Schema::hasTable('consulting_firms')) {
            Schema::create('consulting_firms', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name');
                $table->string('registration_number')->unique();
                $table->text('description');
                $table->string('headquarters_city')->index();
                $table->jsonb('industries')->nullable(); // Retail, IT, Manufacturing, etc.
                $table->integer('rating')->default(0);
                $table->boolean('is_premium')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Consulting firms and business agencies');
            });
        }

        if (!Schema::hasTable('consultants')) {
            Schema::create('consultants', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('consulting_firm_id')->nullable()->constrained('consulting_firms');
                $table->foreignId('user_id')->constrained('users');
                $table->string('full_name');
                $table->string('specialization')->index(); // Finance, Marketing, Coaching, Strategy
                $table->integer('hourly_rate')->default(0); // in cents
                $table->integer('experience_years');
                $table->boolean('is_available')->default(true);
                $table->jsonb('certifications')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Individual business consultants and coaches');
            });
        }

        if (!Schema::hasTable('consulting_services')) {
            Schema::create('consulting_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name');
                $table->text('description');
                $table->integer('base_price')->default(0);
                $table->string('billing_type')->index(); // hourly, fixed, subscription
                $table->jsonb('included_features')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Specific consulting service packages');
            });
        }

        if (!Schema::hasTable('consulting_sessions')) {
            Schema::create('consulting_sessions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('consultant_id')->constrained('consultants');
                $table->foreignId('client_id')->constrained('users');
                $table->dateTime('starts_at');
                $table->integer('duration_minutes')->default(60);
                $table->integer('total_price')->default(0);
                $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
                $table->text('meeting_link')->nullable();
                $table->text('client_notes')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('One-on-one consulting sessions');
            });
        }

        if (!Schema::hasTable('consulting_projects')) {
            Schema::create('consulting_projects', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('consulting_firm_id')->constrained('consulting_firms');
                $table->foreignId('client_id')->constrained('users');
                $table->string('title');
                $table->text('objective');
                $table->integer('budget')->default(0);
                $table->enum('status', ['planning', 'active', 'on_hold', 'finished'])->default('planning');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Long-term consulting projects with multiple deliverables');
            });
        }

        if (!Schema::hasTable('consulting_contracts')) {
            Schema::create('consulting_contracts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('project_id')->nullable()->constrained('consulting_projects');
                $table->foreignId('client_id')->constrained('users');
                $table->string('document_number')->unique();
                $table->text('terms');
                $table->enum('status', ['draft', 'sent', 'active', 'terminated'])->default('draft');
                $table->dateTime('signed_at')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Legal agreements for consulting work');
            });
        }

        if (!Schema::hasTable('consulting_reviews')) {
            Schema::create('consulting_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('consultant_id')->constrained('consultants');
                $table->foreignId('client_id')->constrained('users');
                $table->integer('score'); // 1-100
                $table->text('feedback');
                $table->jsonb('metrics')->nullable(); // impact, expertise, delivery
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Marketplace reviews for consultants');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('consulting_reviews');
        Schema::dropIfExists('consulting_contracts');
        Schema::dropIfExists('consulting_projects');
        Schema::dropIfExists('consulting_sessions');
        Schema::dropIfExists('consulting_services');
        Schema::dropIfExists('consultants');
        Schema::dropIfExists('consulting_firms');
    }
};


