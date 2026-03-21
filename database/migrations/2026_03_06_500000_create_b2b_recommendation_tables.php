<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table for storing AI-generated recommendations for tenants and suppliers.
     */
    public function up(): void
    {
        // This table is likely in the central database for cross-tenant/B2B intelligence 
        // OR inside tenant schema if we want to isolate. 
        // For B2B AI Recommendations, usually it's central or shared in 'stancl/tenancy' context.
        Schema::create('b2b_recommendations', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->uuid('uuid')->unique();
            
            // Scoping - can be for a specific tenant or a global supplier
            $blueprint->string('tenant_id')->nullable()->index(); // ID of the business tenant (buyer)
            $blueprint->unsignedBigInteger('supplier_id')->nullable()->index(); // ID of the B2B supplier (manufacturer)
            
            // The Target entity being recommended
            $blueprint->string('recommendable_type')->comment('B2BProduct or Supplier or Tenant');
            $blueprint->unsignedBigInteger('recommendable_id');
            
            // AI Metadata
            $blueprint->float('match_score')->default(0.0)->index();
            $blueprint->json('reasoning')->nullable()->comment('NLP explanation for the recommendation');
            $blueprint->enum('type', ['SupplierBuy', 'TenantSell', 'Alternative', 'CrossSell'])->index();
            
            // Infrastructure
            $blueprint->string('embeddings_version')->nullable();
            $blueprint->json('telemetry_context')->nullable();
            
            // Tracing & Audit
            $blueprint->uuid('correlation_id')->index();
            $blueprint->timestamps();
            $blueprint->softDeletes();
            
            $blueprint->index(['recommendable_type', 'recommendable_id']);
        });

        // Demand forecast snapshot table
        Schema::create('b2b_demand_forecasts', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('tenant_id')->index();
            $blueprint->string('category_slug')->index();
            $blueprint->unsignedInteger('predicted_quantity_30d');
            $blueprint->float('confidence_interval');
            $blueprint->float('procurement_budget_share');
            $blueprint->uuid('correlation_id');
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b2b_recommendations');
        Schema::dropIfExists('b2b_demand_forecasts');
    }
};
