<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('taxi_fleets')) {
            return;
        }

        Schema::create('taxi_fleets', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Уникальный идентификатор автопарка');
            $table->string('name')->comment('Название автопарка');
            
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            
            $table->string('correlation_id')->nullable()->index();
            $table->jsonb('tags')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('Автопарки такси');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_fleets');
    }
};
