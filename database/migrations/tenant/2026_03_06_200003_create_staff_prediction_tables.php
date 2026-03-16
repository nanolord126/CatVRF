<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: staff prediction tables handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $blueprint->id();
            $blueprint->string('vertical'); // Sports, Education, Events
            $blueprint->string('role_type'); // Coach, Tutor, Security, etc.
            $blueprint->dateTime('forecast_date');
            $blueprint->integer('expected_demand_score'); // 0-100 based on seasonality/events
            $blueprint->integer('current_staff_count');
            $blueprint->integer('forecasted_staff_needed');
            $blueprint->json('contributing_factors'); // ['seasonality' => 0.8, 'local_event_id' => 123]
            $blueprint->decimal('shortage_probability', 5, 2); // 0.00 to 1.00
            $blueprint->string('risk_level'); // Low, Medium, High, Critical
            $blueprint->timestamps();
            
            $blueprint->index(['vertical', 'forecast_date']);
        });

        Schema::create('staff_availability_overrides', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('user_id')->constrained()->onDelete('cascade');
            $blueprint->dateTime('start_time');
            $blueprint->dateTime('end_time');
            $blueprint->string('status'); // Vacation, Sick, Burnout-Risk (AI Detected)
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_availability_overrides');
        Schema::dropIfExists('staff_demand_predictions');
    }
};
