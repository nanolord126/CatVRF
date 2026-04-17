<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clustering_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->integer('cluster_id');
            $table->integer('user_count');
            $table->json('cluster_features')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'cluster_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clustering_results');
    }
};
