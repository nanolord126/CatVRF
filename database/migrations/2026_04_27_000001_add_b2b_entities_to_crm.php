<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_b2b_leads')) {
            Schema::create('crm_b2b_leads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
                $table->string('vertical_id');
                $table->string('company_name');
                $table->string('company_inn')->nullable();
                $table->string('company_kpp')->nullable();
                $table->string('company_legal_address')->nullable();
                $table->string('company_actual_address')->nullable();
                $table->string('contact_person');
                $table->string('contact_position')->nullable();
                $table->string('contact_email');
                $table->string('contact_phone');
                $table->string('website')->nullable();
                $table->string('industry')->nullable();
                $table->string('company_size')->nullable();
                $table->integer('annual_revenue')->nullable();
                $table->text('requirement');
                $table->string('budget_range')->nullable();
                $table->string('category');
                $table->string('status')->default('new');
                $table->string('source')->nullable();
                $table->foreignId('assigned_to_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('assigned_team_id')->nullable()->constrained('teams')->onDelete('set null');
                $table->foreignId('default_warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
                $table->integer('priority')->default(3);
                $table->timestamp('expected_close_date')->nullable();
                $table->integer('probability')->default(50);
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->string('correlation_id')->nullable();
                $table->uuid('uuid')->unique();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'vertical_id']);
                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'assigned_to_id']);
                $table->index('correlation_id');
            });
        }

        if (!Schema::hasTable('crm_b2b_contacts')) {
            Schema::create('crm_b2b_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
                $table->foreignId('lead_id')->nullable()->constrained('crm_b2b_leads')->onDelete('cascade');
                $table->foreignId('deal_id')->nullable()->constrained('crm_b2b_deals')->onDelete('cascade');
                $table->string('name');
                $table->string('position')->nullable();
                $table->string('department')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('mobile')->nullable();
                $table->string('type')->default('client');
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_decision_maker')->default(false);
                $table->string('linkedin')->nullable();
                $table->string('telegram')->nullable();
                $table->string('whatsapp')->nullable();
                $table->text('notes')->nullable();
                $table->json('communication_preferences')->nullable();
                $table->json('metadata')->nullable();
                $table->string('correlation_id')->nullable();
                $table->uuid('uuid')->unique();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'lead_id']);
                $table->index(['tenant_id', 'deal_id']);
            });
        }

        if (!Schema::hasTable('crm_b2b_deals')) {
            Schema::create('crm_b2b_deals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
                $table->string('vertical_id');
                $table->foreignId('lead_id')->nullable()->constrained('crm_b2b_leads')->onDelete('set null');
                $table->foreignId('pipeline_id')->nullable()->constrained('crm_pipelines')->onDelete('set null');
                $table->foreignId('stage_id')->nullable()->constrained('crm_stages')->onDelete('set null');
                $table->string('company_name');
                $table->string('contact_person')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->integer('value')->default(0);
                $table->string('currency')->default('RUB');
                $table->string('status')->default('new');
                $table->integer('priority')->default(3);
                $table->string('contract_type')->nullable();
                $table->timestamp('contract_start_date')->nullable();
                $table->timestamp('contract_end_date')->nullable();
                $table->timestamp('expected_close_date')->nullable();
                $table->timestamp('actual_close_date')->nullable();
                $table->foreignId('assigned_to_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('assigned_team_id')->nullable()->constrained('teams')->onDelete('set null');
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
                $table->integer('probability')->default(50);
                $table->text('won_reason')->nullable();
                $table->text('lost_reason')->nullable();
                $table->json('metadata')->nullable();
                $table->string('correlation_id')->nullable();
                $table->uuid('uuid')->unique();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'vertical_id']);
                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'assigned_to_id']);
                $table->index('correlation_id');
            });
        }

        if (!Schema::hasTable('crm_b2b_lead_tags')) {
            Schema::create('crm_b2b_lead_tags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('crm_b2b_lead_id')->constrained('crm_b2b_leads')->onDelete('cascade');
                $table->foreignId('tag_id')->constrained('crm_tags')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['crm_b2b_lead_id', 'tag_id']);
            });
        }

        if (Schema::hasTable('crm_tasks')) {
            if (!Schema::hasColumn('crm_tasks', 'entity_type')) {
                Schema::table('crm_tasks', function (Blueprint $table) {
                    $table->string('entity_type')->nullable()->after('deal_id');
                    $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
                    $table->index(['entity_type', 'entity_id']);
                });
            }
        }

        if (Schema::hasTable('crm_interactions')) {
            if (!Schema::hasColumn('crm_interactions', 'entity_type')) {
                Schema::table('crm_interactions', function (Blueprint $table) {
                    $table->string('entity_type')->default('deal')->after('deal_id');
                    $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
                    $table->index(['entity_type', 'entity_id']);
                });
            }
        }

        if (Schema::hasTable('inventory_requests')) {
            if (!Schema::hasColumn('inventory_requests', 'b2b_lead_id')) {
                Schema::table('inventory_requests', function (Blueprint $table) {
                    $table->foreignId('b2b_lead_id')->nullable()->after('tenant_id')->constrained('crm_b2b_leads')->onDelete('set null');
                    $table->index('b2b_lead_id');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_b2b_lead_tags');
        Schema::dropIfExists('crm_b2b_deals');
        Schema::dropIfExists('crm_b2b_contacts');
        Schema::dropIfExists('crm_b2b_leads');

        if (Schema::hasTable('crm_interactions')) {
            Schema::table('crm_tasks', function (Blueprint $table) {
                $table->dropColumn(['entity_type', 'entity_id']);
            });
        }

        if (Schema::hasTable('inventory_requests')) {
            Schema::table('crm_interactions', function (Blueprint $table) {
                $table->dropColumn(['entity_type', 'entity_id']);
            });
    
        }
        Schema::table('inventory_requests', function (Blueprint $table) {
            $table->dropForeign(['b2b_lead_id']);
            $table->dropColumn('b2b_lead_id');
        });
    }
};
