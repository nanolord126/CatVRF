<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medical_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users');
            $table->string('blood_type')->nullable();
            $table->json('allergies')->nullable();
            $table->json('medical_history')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_check_up')->nullable();
            $table->timestamps();
            $table->index('tenant_id');

            $table->string('correlation_id')->nullable()->index();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_cards');
    }
};

