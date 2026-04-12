<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['email', 'push', 'sms', 'in_app', 'shorts', 'banner', 'social', 'mixed'])->index();
            $table->enum('status', ['draft', 'scheduled', 'active', 'paused', 'completed', 'cancelled'])->default('draft')->index();
            $table->decimal('budget', 14, 2)->default(0);
            $table->decimal('spent', 14, 2)->default(0);
            $table->json('targeting')->nullable()->comment('Targeting criteria JSON: taste_profile, geo, device, behavior, b2c/b2b');
            $table->json('ab_variants')->nullable()->comment('A/B test variants configuration');
            $table->string('vertical', 50)->nullable()->index();
            $table->enum('audience_segment', ['new', 'returning', 'churning', 'vip', 'b2b', 'all'])->default('all');
            $table->unsignedInteger('estimated_reach')->default(0);
            $table->unsignedInteger('actual_reach')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->decimal('ctr', 8, 4)->default(0)->comment('Click-through rate');
            $table->decimal('conversion_rate', 8, 4)->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('correlation_id')->index();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status', 'type']);
            $table->index(['tenant_id', 'vertical', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};
