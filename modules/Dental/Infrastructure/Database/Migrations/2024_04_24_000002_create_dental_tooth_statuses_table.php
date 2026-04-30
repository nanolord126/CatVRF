<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_tooth_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tooth_chart_id')->constrained('dental_tooth_charts')->onDelete('cascade');
            $table->string('tooth_number'); // FDI: 11-48, Universal: 1-32, Milk: 51-85
            $table->enum('status', [
                'healthy',
                'caries',
                'filled',
                'crown',
                'implant',
                'extracted',
                'root_canal',
                'mobility_1',
                'mobility_2',
                'mobility_3',
                'missing',
                'impacted',
                'supernumerary',
            ]);
            $table->string('color')->nullable(); // For visualization
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('performed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tooth_chart_id', 'tooth_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_tooth_statuses');
    }
};
