<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_slots', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64)->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('teacher_id')->nullable()->constrained('education_teachers')->onDelete('set null');
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('set null');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->integer('duration_minutes');
            $table->integer('capacity');
            $table->integer('booked_count')->default(0);
            $table->enum('slot_type', ['webinar', 'tutoring', 'exam', 'consultation'])->default('tutoring');
            $table->enum('status', ['available', 'held', 'booked', 'cancelled', 'completed'])->default('available');
            $table->string('meeting_link')->nullable();
            $table->string('meeting_password')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'start_time']);
            $table->index(['teacher_id', 'start_time']);
            $table->index('start_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_slots');
    }
};
