<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: AI matching enhancement handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
                if (!Schema::hasColumn('hr_vacancy_matches', 'semantic_score')) {
                    $table->float('semantic_score')->default(0)->after('match_score');
                }
                if (!Schema::hasColumn('hr_vacancy_matches', 'skill_score')) {
                    $table->float('skill_score')->default(0)->after('semantic_score');
                }
                if (!Schema::hasColumn('hr_vacancy_matches', 'geo_score')) {
                    $table->float('geo_score')->default(0)->after('skill_score');
                }
                if (!Schema::hasColumn('hr_vacancy_matches', 'correlation_id')) {
                    $table->uuid('correlation_id')->nullable()->index();
                }
            });
        }

        // 2. B2B Manufacturer Recommendations for Tenants
        Schema::create('b2b_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_id')->constrained('b2b_manufacturers')->onDelete('cascade');
            $table->float('match_score')->default(0);
            $table->float('reliability_score')->default(0);
            $table->float('pricing_score')->default(0);
            $table->float('geo_score')->default(0);
            $table->json('reasons')->nullable();
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();
            
            // Note: In schema-per-tenant, we don't need tenant_id, 
            // but we might need it if we are in a central schema tracking global recs.
            // Keeping it local to tenant schema as per Canon.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b2b_recommendations');
        
        if (Schema::hasTable('hr_vacancy_matches')) {
            Schema::table('hr_vacancy_matches', function (Blueprint $table) {
                $table->dropColumn(['semantic_score', 'skill_score', 'geo_score', 'correlation_id']);
            });
        }
    }
};
