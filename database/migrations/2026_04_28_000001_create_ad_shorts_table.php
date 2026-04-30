<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_shorts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('video_url');
            $table->string('thumbnail_url');
            $table->integer('duration_seconds');
            $table->enum('status', ['draft', 'pending_review', 'active', 'paused', 'completed', 'rejected', 'cancelled'])
                ->default('draft');
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->bigInteger('budget')->default(0);
            $table->bigInteger('spent')->default(0);
            $table->enum('pricing_model', ['cpm', 'cpc', 'cpa', 'cpv'])->default('cpm');
            $table->json('targeting_criteria')->nullable();
            $table->string('correlation_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'start_at', 'end_at']);
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_shorts');
    }
};
