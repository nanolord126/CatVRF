<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_indices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('searchable_type');
            $table->integer('searchable_id');
            $table->string('title');
            $table->text('content')->nullable();
            $table->json('metadata')->nullable();
            $table->float('ranking_score')->default(0.0);
            $table->timestamps();

            $table->index(['tenant_id', 'searchable_type']);
            $table->index(['tenant_id', 'searchable_type', 'searchable_id']);
            $table->index('ranking_score');

            // Skip fullText for SQLite
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(['title', 'content']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_indices');
    }
};
