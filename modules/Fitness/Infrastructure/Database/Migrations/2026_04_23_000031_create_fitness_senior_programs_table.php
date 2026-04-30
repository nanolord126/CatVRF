<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_senior_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            $table->string('name');
            $table->text('description');
            $table->string('focus_area');
            $table->integer('duration_weeks');
            $table->integer('sessions_per_week');
            $table->integer('session_duration_minutes');
            $table->decimal('price', 10, 2);
            $table->integer('max_participants')->nullable();
            $table->json('exercises')->nullable();
            $table->json('safety_requirements')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'focus_area']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_senior_programs');
    }
};
