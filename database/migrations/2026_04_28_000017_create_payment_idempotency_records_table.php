<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_idempotency_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('idempotency_key', 255)->index();
            $table->string('operation', 50); // payment_init, payment_capture, etc.
            $table->string('payload_hash', 64); // SHA256 hash of payload
            $table->json('cached_response')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('created_at');
            
            $table->unique(['idempotency_key', 'expires_at']);
            $table->index(['tenant_id', 'operation']);
            $table->index(['tenant_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_idempotency_records');
    }
};
