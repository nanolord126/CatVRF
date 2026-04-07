<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('beauty_portfolio_items')) {
            return;
        }

        Schema::create('beauty_portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            $table->foreignId('master_id')->constrained('masters')->onDelete('cascade');
            
            $table->string('image_url')->comment('URL изображения работы');
            $table->text('description')->nullable()->comment('Описание работы');
            
            $table->jsonb('tags')->nullable()->comment('Теги для фильтрации и аналитики');
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Портфолио работ мастеров в вертикали "Красота"');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beauty_portfolio_items');
    }
};


