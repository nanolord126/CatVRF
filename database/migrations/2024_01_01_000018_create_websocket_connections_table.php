<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websocket_connections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('channel');
            $table->string('connection_id');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'channel']);
            $table->index('connection_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('websocket_connections');
    }
};
