<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('taxi_surge_zones')) {
            return;
        }

        Schema::create('taxi_surge_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name')->comment('Название зоны повышенного спроса');
            $table->geometry('polygon', subtype: 'polygon', srid: 4326)->comment('Полигон зоны');
            $table->decimal('surge_multiplier', 3, 2)->default(1.00)->comment('Коэффициент surge (1.0 — нет surge)');
            $table->boolean('is_active')->default(true);
            $table->string('correlation_id')->nullable()->index();
            $table->jsonb('tags')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->comment('Гео-зоны повышенного спроса такси');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_surge_zones');
    }
};
