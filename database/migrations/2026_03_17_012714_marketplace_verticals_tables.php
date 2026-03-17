<?php

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
        // -------------------------
        // EVENTS MODULE
        // -------------------------

        Schema::create('venues', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name')->index();
            $blueprint->string('address');
            $blueprint->json('geo_location')->nullable();
            $blueprint->integer('capacity')->default(0);
            $blueprint->json('hall_layout')->nullable(); // For Konva.js
            $blueprint->string('correlation_id')->index();
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });

        Schema::create('events', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('venue_id')->constrained();
            $blueprint->string('title')->index();
            $blueprint->text('description')->nullable();
            $blueprint->dateTime('start_at')->index();
            $blueprint->dateTime('end_at')->index();
            $blueprint->enum('status', ['draft', 'published', 'cancelled', 'completed'])->default('draft');
            $blueprint->json('seating_data')->nullable(); // Seating configuration
            $blueprint->string('correlation_id')->index();
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });

        Schema::create('tickets', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('event_id')->constrained();
            $blueprint->string('category')->index(); // e.g., VIP, Standard
            $blueprint->decimal('price', 14, 2);
            $blueprint->integer('quantity_available');
            $blueprint->json('meta_data')->nullable();
            $blueprint->string('correlation_id')->index();
            $blueprint->timestamps();
        });

        Schema::create('bookings', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('user_id')->constrained();
            $blueprint->foreignId('event_id')->constrained();
            $blueprint->json('seat_numbers')->nullable();
            $blueprint->decimal('total_price', 14, 2);
            $blueprint->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $blueprint->string('invoice_link')->nullable();
            $blueprint->string('correlation_id')->index();
            $blueprint->timestamps();
        });

        // -------------------------
        // SPORTS MODULE
        // -------------------------

        Schema::create('gyms', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name')->index();
            $blueprint->string('address');
            $blueprint->json('geo_location')->nullable();
            $blueprint->json('occupancy_data')->nullable(); // For attendance heatmaps
            $blueprint->string('correlation_id')->index();
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });

        Schema::create('coaches', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('user_id')->constrained();
            $blueprint->string('specialization')->index();
            $blueprint->text('bio')->nullable();
            $blueprint->decimal('hourly_rate', 14, 2)->default(0);
            $blueprint->string('correlation_id')->index();
            $blueprint->timestamps();
        });

        Schema::create('training_schedules', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('gym_id')->constrained();
            $blueprint->foreignId('coach_id')->constrained();
            $blueprint->string('training_type')->index(); // Yoga, HIIT, etc.
            $blueprint->dateTime('start_at')->index();
            $blueprint->dateTime('end_at');
            $blueprint->integer('max_participants')->default(10);
            $blueprint->unsignedBigInteger('inventory_id')->nullable(); // Future WMS link
            $blueprint->string('correlation_id')->index();
            $blueprint->timestamps();
        });

        Schema::create('nutrition_plans', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('user_id')->constrained();
            $blueprint->foreignId('coach_id')->constrained();
            $blueprint->string('title');
            $blueprint->json('plan_details');
            $blueprint->date('start_date');
            $blueprint->date('end_date')->nullable();
            $blueprint->string('correlation_id')->index();
            $blueprint->timestamps();
        });

        // -------------------------
        // EDUCATION MODULE
        // -------------------------

        Schema::create('courses', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('title')->index();
            $blueprint->text('description')->nullable();
            $blueprint->string('category')->index();
            $blueprint->decimal('price', 14, 2)->default(0);
            $blueprint->string('correlation_id')->index();
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });

        Schema::create('course_modules', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('course_id')->constrained()->onDelete('cascade');
            $blueprint->string('title');
            $blueprint->integer('sort_order')->default(0);
            $blueprint->string('correlation_id')->index();
            $blueprint->timestamps();
        });

        Schema::create('lessons', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('module_id')->constrained('course_modules')->onDelete('cascade');
            $blueprint->string('title');
            $blueprint->string('video_url')->nullable();
            $blueprint->text('content')->nullable();
            $blueprint->integer('sort_order')->default(0);
            $blueprint->string('correlation_id')->index();
            $blueprint->timestamps();
        });

        Schema::create('education_progress', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('user_id')->constrained();
            $blueprint->foreignId('lesson_id')->constrained();
            $blueprint->float('progress_percentage')->default(0);
            $blueprint->boolean('is_completed')->default(false);
            $blueprint->string('correlation_id')->index();
            $blueprint->timestamps();
            $blueprint->unique(['user_id', 'lesson_id']);
        });

        Schema::create('call_sessions', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('user_id')->constrained();
            $blueprint->string('room_id')->unique(); // For WebRTC logic
            $blueprint->dateTime('scheduled_at')->index();
            $blueprint->integer('duration_minutes')->default(30);
            $blueprint->string('correlation_id')->index();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_sessions');
        Schema::dropIfExists('education_progress');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('nutrition_plans');
        Schema::dropIfExists('training_schedules');
        Schema::dropIfExists('coaches');
        Schema::dropIfExists('gyms');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('events');
        Schema::dropIfExists('venues');
    }
};
