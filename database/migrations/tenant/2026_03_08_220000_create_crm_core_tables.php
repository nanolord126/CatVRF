<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pipelines', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable(); $table->string('tenant_id')->index();
            $table->string('correlation_id')->nullable(); $table->timestamps();
        });

        Schema::create('stages', function (Blueprint $table) {
            $table->id(); $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->string('name'); $table->string('color')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_win')->default(false); $table->boolean('is_loss')->default(false);
            $table->timestamps();
        });

        Schema::create('deals', function (Blueprint $table) {
            $table->id(); $table->string('name');
            $table->foreignId('pipeline_id')->constrained(); $table->foreignId('stage_id')->constrained();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('amount', 15, 2)->default(0); $table->string('currency', 3)->default('RUB');
            $table->timestamp('closed_at')->nullable(); $table->string('tenant_id')->index();
            $table->string('correlation_id')->nullable(); $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->text('description')->nullable();
            $table->string('status')->default('active'); $table->date('deadline')->nullable();
            $table->string('tenant_id')->index(); $table->string('correlation_id')->nullable();
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id(); $table->string('title'); $table->text('description')->nullable();
            $table->enum('status', ['new', 'in_progress', 'pending', 'completed', 'deferred'])->default('new');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->unsignedBigInteger('creator_id'); $table->unsignedBigInteger('responsible_id');
            $table->foreignId('project_id')->nullable()->constrained();
            $table->timestamp('due_at')->nullable(); $table->timestamp('remind_at')->nullable();
            $table->timestamp('completed_at')->nullable(); $table->string('tenant_id')->index();
            $table->string('correlation_id')->nullable(); $table->timestamps();
        });

        Schema::create('robots', function (Blueprint $table) {
            $table->id(); $table->string('name');
            $table->string('trigger_type'); $table->json('trigger_config');
            $table->string('action_type'); $table->json('action_config');
            $table->boolean('is_active')->default(true); $table->string('tenant_id')->index();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('robots'); Schema::dropIfExists('tasks');
        Schema::dropIfExists('projects'); Schema::dropIfExists('deals');
        Schema::dropIfExists('stages'); Schema::dropIfExists('pipelines');
    }
};
