<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fashion_user_size_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->decimal('height', 5, 2)->nullable(); // in cm
            $table->decimal('weight', 5, 2)->nullable(); // in kg
            $table->decimal('chest', 5, 2)->nullable(); // in cm
            $table->decimal('waist', 5, 2)->nullable(); // in cm
            $table->decimal('hips', 5, 2)->nullable(); // in cm
            $table->decimal('shoulder_width', 5, 2)->nullable(); // in cm
            $table->decimal('arm_length', 5, 2)->nullable(); // in cm
            $table->decimal('leg_length', 5, 2)->nullable(); // in cm
            $table->decimal('shoe_size', 4, 1)->nullable(); // EU size
            $table->timestamps();
            
            $table->unique(['user_id', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fashion_user_size_profiles');
    }
};
