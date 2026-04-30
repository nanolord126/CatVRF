<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_seasonal_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['group', 'individual', 'hybrid'])->default('group');
            
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->integer('duration_weeks')->default(8);
            $table->integer('sessions_per_week')->default(3);
            
            $table->decimal('price', 10, 2)->default(0.00);
            $table->integer('max_participants')->nullable();
            
            $table->json('goals')->nullable();
            $table->text('requirements')->nullable();
            
            $table->enum('status', ['draft', 'active', 'full', 'completed', 'archived', 'cancelled'])
                ->default('draft');
            $table->integer('current_participants')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'period_start', 'period_end']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_seasonal_programs');
    }
};
