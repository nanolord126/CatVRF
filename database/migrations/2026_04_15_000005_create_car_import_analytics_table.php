<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_import_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->date('analysis_date')->index();
            $table->json('analysis_data');
            $table->string('correlation_id')->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'analysis_date']);
            $table->index(['tenant_id', 'analysis_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_import_analytics');
    }
};
