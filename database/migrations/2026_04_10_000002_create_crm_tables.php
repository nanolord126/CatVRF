<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблицы CRM-системы: crm_leads, crm_tasks.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('source', 100)->nullable();
            $table->string('vertical', 50)->nullable();
            $table->string('status', 30)->default('new');
            $table->unsignedInteger('expected_value')->default(0); // в рублях
            $table->text('notes')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['assigned_to', 'follow_up_at']);
            $table->comment('CRM leads pipeline');
        });

        Schema::create('crm_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('related_lead_id')->nullable()->constrained('crm_leads')->onDelete('set null');
            $table->string('title');
            $table->string('type', 30)->default('call');
            $table->string('priority', 20)->default('normal');
            $table->string('status', 30)->default('open');
            $table->text('description')->nullable();
            $table->text('result')->nullable();
            $table->timestamp('due_at');
            $table->string('correlation_id', 64)->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'due_at']);
            $table->index(['assignee_id', 'status']);
            $table->comment('CRM tasks for operators');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_tasks');
        Schema::dropIfExists('crm_leads');
    }
};
