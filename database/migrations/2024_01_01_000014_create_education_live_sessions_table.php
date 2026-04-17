<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_live_sessions', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('slot_id')->nullable()->constrained('education_slots')->onDelete('set null');
            $table->foreignId('teacher_id')->nullable()->constrained('education_teachers')->onDelete('set null');
            $table->string('meeting_id', 64);
            $table->string('teacher_token')->nullable();
            $table->enum('status', ['scheduled', 'active', 'ended'])->default('scheduled');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('participant_count')->default(0);
            $table->string('correlation_id', 64)->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'teacher_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['slot_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_live_sessions');
    }
};
