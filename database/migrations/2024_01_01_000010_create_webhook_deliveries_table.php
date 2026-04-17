<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('webhook_id')->constrained()->onDelete('cascade');
            $table->string('event_type');
            $table->json('payload');
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->json('response_headers')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->string('correlation_id', 36)->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'webhook_id']);
            $table->index(['webhook_id', 'event_type']);
            $table->index('next_retry_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
