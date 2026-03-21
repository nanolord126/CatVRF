<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('crm_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('crm_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_pipeline_id')->constrained('crm_pipelines')->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('crm_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_stage_id')->constrained('crm_stages')->cascadeOnDelete();
            $table->string('title');
            $table->decimal('value', 15, 2)->default(0);
            $table->string('currency', 3)->default('RUB');
            $table->foreignId('contact_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['todo', 'in_progress', 'completed', 'cancelled'])->default('todo');
            $table->dateTime('due_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained('crm_deals')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('crm_robot_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_stage_id')->constrained('crm_stages')->cascadeOnDelete();
            $table->string('name');
            $table->string('action_type'); // create_task, send_notification, change_stage
            $table->json('settings')->nullable();
            $table->string('trigger_event')->default('entry'); // entry, time_offset, field_change
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_robot_rules');
        Schema::dropIfExists('crm_tasks');
        Schema::dropIfExists('crm_deals');
        Schema::dropIfExists('crm_stages');
        Schema::dropIfExists('crm_pipelines');
    }
};
