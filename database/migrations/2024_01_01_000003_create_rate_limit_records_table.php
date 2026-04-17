<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_limit_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('operation');
            $table->string('key');
            $table->integer('attempts')->default(0);
            $table->integer('limit');
            $table->integer('window_seconds');
            $table->timestamp('blocked_until')->nullable();
            $table->string('correlation_id', 36)->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'operation']);
            $table->index('key');
            $table->index('blocked_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_limit_records');
    }
};
