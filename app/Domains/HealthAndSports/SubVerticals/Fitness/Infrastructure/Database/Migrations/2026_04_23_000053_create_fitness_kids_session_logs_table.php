<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_kids_session_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('enrollment_id')->constrained('fitness_kids_program_enrollments')->onDelete('cascade');
            
            $table->date('session_date');
            $table->string('activity_type')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->string('mood')->nullable();
            $table->text('notes')->nullable();
            $table->json('vitals')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'session_date']);
            $table->index(['enrollment_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_kids_session_logs');
    }
};
