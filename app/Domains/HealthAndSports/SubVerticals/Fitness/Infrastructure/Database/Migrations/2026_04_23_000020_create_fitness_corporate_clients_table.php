<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_corporate_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            $table->string('name');
            $table->string('inn', 12)->unique();
            $table->string('legal_address');
            $table->string('contact_person');
            $table->string('hr_email');
            $table->string('phone_number')->nullable();
            $table->string('contract_number')->nullable();
            $table->date('contract_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'inn']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_corporate_clients');
    }
};
