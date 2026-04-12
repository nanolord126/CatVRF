<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('channel', ['email', 'push', 'sms', 'in_app'])->index();
            $table->string('subject')->nullable()->comment('Email subject or Push title');
            $table->text('body_html')->nullable()->comment('HTML body for email');
            $table->text('body_text')->nullable()->comment('Plain text body / SMS text');
            $table->json('body_json')->nullable()->comment('Structured content for in-app / push');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->string('vertical', 50)->nullable()->index();
            $table->enum('audience_segment', ['new', 'returning', 'churning', 'vip', 'b2b', 'all'])->default('all');
            $table->json('personalization_fields')->nullable()->comment('List of dynamic fields: {user_name}, {vertical}, {taste_profile}');
            $table->json('ab_variants')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->float('avg_open_rate')->default(0);
            $table->float('avg_click_rate')->default(0);
            $table->string('correlation_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'channel', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_templates');
    }
};
