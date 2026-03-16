<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Use tenant schema as per stancl/tenancy.
     */
    public function up(): void
    {
        // 1. HR Job Vacancies Table
        Schema::create('hr_job_vacancies', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('title')->index();
            $blueprint->text('description');
            
            // Requirements & Metadata
            $blueprint->json('skills')->nullable(); // Required skills array
            $blueprint->decimal('salary_min', 15, 2)->nullable();
            $blueprint->decimal('salary_max', 15, 2)->nullable();
            $blueprint->string('currency')->default('USD');
            
            // Multi-vertical Scoping
            $blueprint->string('vertical')->comment('Taxi, Food, Retail, etc.')->index();
            
            // Geo & Logistics
            $blueprint->string('location_name')->nullable();
            $blueprint->decimal('latitude', 10, 8)->nullable();
            $blueprint->decimal('longitude', 11, 8)->nullable();
            
            // Status & AI Tracking
            $blueprint->enum('status', ['draft', 'open', 'closed', 'on_hold'])->default('open')->index();
            $blueprint->uuid('correlation_id')->nullable()->index();
            
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });

        // 2. HR Resumes Table (Linked to User/Staff)
        Schema::create('hr_resumes', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $blueprint->json('experience_history')->nullable();
            $blueprint->json('skills')->nullable();
            $blueprint->json('portfolio_links')->nullable();
            
            // AI Analysis Data
            $blueprint->float('ai_talent_score')->default(0.0)->index();
            $blueprint->json('ai_skills_analysis')->nullable();
            
            $blueprint->uuid('correlation_id')->nullable()->index();
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });

        // 3. Vacancy Matches (Cache for recommended staff)
        Schema::create('hr_vacancy_matches', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('vacancy_id')->constrained('hr_job_vacancies')->cascadeOnDelete();
            $blueprint->foreignId('user_id')->constrained()->cascadeOnDelete(); // The Candidate
            
            $blueprint->float('match_score')->index(); // 0.0 - 1.0
            $blueprint->json('match_reasons')->nullable(); // Breakdown by AI
            
            $blueprint->timestamps();
            
            $blueprint->unique(['vacancy_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_vacancy_matches');
        Schema::dropIfExists('hr_resumes');
        Schema::dropIfExists('hr_job_vacancies');
    }
};
