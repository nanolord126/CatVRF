<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_achievements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('badge_id');
            $table->string('badge_name');
            $table->string('badge_icon');
            $table->integer('points_awarded')->default(0);
            $table->timestamp('achieved_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('tenant_id');
            $table->index('employee_id');
            $table->index('badge_id');
            $table->index('achieved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_achievements');
    }
};
