<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create analytics_retention_cohorts table
 *
 * Stores cohort retention analysis data.
 * Cohorts: users/sellers grouped by signup date, tracked over time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_retention_cohorts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenant_id')->index();
            $table->string('cohort_type', 20)->index(); // user, seller
            $table->string('cohort_name', 100)->index();
            $table->date('cohort_date')->index();
            $table->unsignedInteger('cohort_size')->default(0);
            
            // Retention rates by period (JSON: period_0, period_1, period_2, etc.)
            $table->json('retention_rates');
            
            $table->timestamp('calculated_at')->index();
            
            $table->unique(['tenant_id', 'cohort_type', 'cohort_name', 'cohort_date']);
            $table->index(['cohort_date', 'cohort_type']);
            
            $table->timestamps();
        });

        // Note: DB::statement() requires MySQL 5.7+ or MariaDB 10.2+
        // For PostgreSQL, use COMMENT ON TABLE syntax instead
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_retention_cohorts');
    }
};
