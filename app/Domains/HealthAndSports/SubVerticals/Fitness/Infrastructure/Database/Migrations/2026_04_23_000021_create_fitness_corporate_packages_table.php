<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_corporate_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            $table->string('name');
            $table->text('description');
            $table->decimal('price_per_employee', 10, 2);
            $table->integer('min_employees');
            $table->integer('max_employees')->nullable();
            $table->integer('duration_months');
            $table->json('included_services')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_corporate_packages');
    }
};
