<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['mdlp', 'mercury']);
            $table->string('document_id');
            $table->enum('status', ['pending', 'verified', 'failed'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->json('response_data')->nullable();
            $table->string('correlation_id', 36)->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'status']);
            $table->index('document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_records');
    }
};
