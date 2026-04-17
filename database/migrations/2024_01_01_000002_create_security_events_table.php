<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event_type');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 36)->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'event_type']);
            $table->index(['tenant_id', 'severity']);
            $table->index('created_at');
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
