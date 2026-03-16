<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: digital twin tables handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $table->id();
            $table->string('scenario_name');
            $table->string('target_vertical'); // taxi, food, clinic, global
            $table->json('input_parameters'); // { "tariff_change": 1.15, "staff_count_change": -5 }
            $table->json('prediction_results')->nullable(); // { "predicted_revenue": 500000, "churn_delta": 0.05 }
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->float('confidence_interval')->default(0.95);
            $table->text('ai_summary')->nullable();
            $table->timestamps();
        });

        // 2. Historical Baselines for Simulation Training
        Schema::create('simulation_baselines', function (Blueprint $table) {
            $table->id();
            $table->string('vertical');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('avg_daily_revenue', 15, 2);
            $table->integer('avg_staff_load');
            $table->float('avg_conversion_rate');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulation_baselines');
        Schema::dropIfExists('business_simulations');
    }
};
