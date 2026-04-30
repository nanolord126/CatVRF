<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_prenatal_health_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained('fitness_clients')->onDelete('cascade');
            
            $table->date('due_date');
            $table->enum('trimester', ['first', 'second', 'third'])->default('first');
            $table->boolean('has_high_risk_pregnancy')->default(false);
            $table->boolean('has_preeclampsia_risk')->default(false);
            $table->boolean('has_gestational_diabetes')->default(false);
            $table->text('obstetrician_notes')->nullable();
            $table->text('medications')->nullable();
            $table->text('allergies')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->json('physical_limitations')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['client_id']);
            $table->index(['tenant_id', 'trimester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_prenatal_health_profiles');
    }
};
