<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for LegalServices vertical.
     * Implementing 9-LAYER ARCHITECTURE 2026.
     * Tables: law_firms, lawyers, legal_services, legal_consultations, legal_contracts, legal_reviews.
     */
    public function up(): void
    {
        if (!Schema::hasTable('law_firms')) {
            Schema::create('law_firms', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name');
                $table->string('license_number')->unique();
                $table->text('address');
                $table->string('city')->index();
                $table->jsonb('specializations')->nullable();
                $table->integer('rating')->default(0);
                $table->boolean('is_verified')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Law firms and legal agencies in the marketplace');
            });
        }

        if (!Schema::hasTable('lawyers')) {
            Schema::create('lawyers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('law_firm_id')->nullable()->constrained('law_firms');
                $table->foreignId('user_id')->constrained('users');
                $table->string('full_name');
                $table->string('registration_number')->unique();
                $table->jsonb('categories')->nullable(); // civil, criminal, corporate, notary
                $table->integer('experience_years');
                $table->integer('consultation_price')->default(0); // in cents
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Individual lawyers and advocates');
            });
        }

        if (!Schema::hasTable('legal_services')) {
            Schema::create('legal_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->string('name');
                $table->text('description');
                $table->integer('base_price')->default(0);
                $table->string('type')->index(); // consultation, document_draft, representation
                $table->jsonb('metadata')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Specific legal services offered');
            });
        }

        if (!Schema::hasTable('legal_consultations')) {
            Schema::create('legal_consultations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('lawyer_id')->constrained('lawyers');
                $table->foreignId('client_id')->constrained('users');
                $table->dateTime('scheduled_at');
                $table->integer('duration_minutes')->default(60);
                $table->integer('price')->default(0);
                $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
                $table->enum('type', ['online', 'offline'])->default('online');
                $table->text('summary')->nullable(); // Confidential summary
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Legal consultation bookings');
            });
        }

        if (!Schema::hasTable('legal_contracts')) {
            Schema::create('legal_contracts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('consultation_id')->nullable()->constrained('legal_consultations');
                $table->foreignId('client_id')->constrained('users');
                $table->string('title');
                $table->text('content');
                $table->enum('status', ['draft', 'signed', 'completed', 'archived'])->default('draft');
                $table->dateTime('signed_at')->nullable();
                $table->jsonb('digital_signature')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Legal documents and contracts generated or managed');
            });
        }

        if (!Schema::hasTable('legal_reviews')) {
            Schema::create('legal_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('lawyer_id')->constrained('lawyers');
                $table->foreignId('client_id')->constrained('users');
                $table->integer('rating');
                $table->text('comment');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['lawyer_id', 'client_id']);
                $table->comment('Client reviews for lawyers');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_reviews');
        Schema::dropIfExists('legal_contracts');
        Schema::dropIfExists('legal_consultations');
        Schema::dropIfExists('legal_services');
        Schema::dropIfExists('lawyers');
        Schema::dropIfExists('law_firms');
    }
};


