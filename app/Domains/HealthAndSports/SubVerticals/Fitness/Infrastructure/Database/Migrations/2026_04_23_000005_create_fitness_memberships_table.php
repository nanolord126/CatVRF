<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained('fitness_clients')->onDelete('cascade');
            $table->enum('type', ['monthly', 'quarterly', 'annual', 'unlimited', 'punch_card', 'corporate', 'trial', 'senior', 'student']);
            $table->enum('status', ['active', 'frozen', 'expired', 'cancelled', 'suspended'])->default('active');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('remaining_visits')->nullable();
            $table->integer('total_visits')->nullable();
            $table->decimal('price', 10, 2);
            $table->boolean('allow_freeze')->default(true);
            $table->integer('freeze_days_used')->default(0);
            $table->integer('max_freeze_days')->default(30);
            $table->timestamp('frozen_at')->nullable();
            $table->timestamp('unfrozen_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'client_id', 'status']);
            $table->index(['tenant_id', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_memberships');
    }
};
