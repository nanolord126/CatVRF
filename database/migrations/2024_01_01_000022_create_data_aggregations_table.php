<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_aggregations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('source');
            $table->string('aggregation_type');
            $table->string('aggregation_key');
            $table->float('value');
            $table->timestamp('timestamp')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'source']);
            $table->index(['tenant_id', 'aggregation_type']);
            $table->index('timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_aggregations');
    }
};
