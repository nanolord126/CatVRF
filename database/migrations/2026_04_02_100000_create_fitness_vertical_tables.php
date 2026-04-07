<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для вертикали Fitness.
 * Канон CatVRF 2026 — все таблицы содержат uuid, correlation_id, tags, tenant_id, business_group_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Фитнес-клубы / залы
        Schema::create('fitness_gyms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->string('address');
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lon', 11, 8)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('description')->nullable();
            $table->json('amenities')->nullable();        // ['pool', 'sauna', 'parking', ...]
            $table->json('working_hours')->nullable();    // {"mon": "08:00-22:00", ...}
            $table->boolean('is_active')->default(true);
            $table->string('status', 30)->default('active'); // active | suspended | closed
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        // Тренеры / инструкторы
        Schema::create('fitness_trainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('gym_id')->constrained('fitness_gyms')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('full_name');
            $table->string('specialization');           // yoga | crossfit | boxing | rehabilitation | ...
            $table->text('bio')->nullable();
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->string('photo_url')->nullable();
            $table->json('certifications')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['gym_id', 'is_active']);
            $table->index(['tenant_id', 'specialization']);
        });

        // Планы тренировок
        Schema::create('fitness_workout_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('fitness_trainers')->nullOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->string('goal', 60);                  // weight_loss | muscle_gain | endurance | rehabilitation
            $table->unsignedSmallInteger('duration_weeks')->default(8);
            $table->unsignedTinyInteger('sessions_per_week')->default(3);
            $table->json('exercises')->nullable();        // {"monday": [...], "wednesday": [...]}
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        // Абонементы
        Schema::create('fitness_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('gym_id')->constrained('fitness_gyms')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->index();
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('type', 30)->default('standard'); // standard | premium | vip | corporate
            $table->unsignedSmallInteger('duration_days')->default(30);
            $table->decimal('price', 14, 2);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sessions_included')->default(0);
            $table->unsignedSmallInteger('sessions_used')->default(0);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['gym_id', 'expires_at']);
            $table->index(['tenant_id', 'type']);
        });

        // Занятия / записи к тренеру
        Schema::create('fitness_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('gym_id')->constrained('fitness_gyms')->onDelete('cascade');
            $table->foreignId('trainer_id')->nullable()->constrained('fitness_trainers')->nullOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamp('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('status', 30)->default('confirmed'); // confirmed | completed | cancelled | no_show
            $table->string('type', 30)->default('personal');    // personal | group | online
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'scheduled_at']);
            $table->index(['trainer_id', 'scheduled_at']);
            $table->index(['gym_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_sessions');
        Schema::dropIfExists('fitness_memberships');
        Schema::dropIfExists('fitness_workout_plans');
        Schema::dropIfExists('fitness_trainers');
        Schema::dropIfExists('fitness_gyms');
    }
};
