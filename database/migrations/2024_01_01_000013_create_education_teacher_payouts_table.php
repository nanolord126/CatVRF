<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_teacher_payouts', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('teacher_id')->nullable()->constrained('education_teachers')->onDelete('set null');
            $table->foreignId('slot_id')->nullable()->constrained('education_slots')->onDelete('set null');
            $table->integer('amount_kopecks');
            $table->enum('status', ['frozen', 'released', 'failed'])->default('frozen');
            $table->timestamp('frozen_at');
            $table->timestamp('scheduled_release_at');
            $table->timestamp('released_at')->nullable();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'teacher_id']);
            $table->index(['tenant_id', 'slot_id']);
            $table->index(['teacher_id', 'status']);
            $table->index(['scheduled_release_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_teacher_payouts');
    }
};
