<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('healthy_foods')) return;

        Schema::create('healthy_foods', function (Blueprint $table) {
            $table->id()->comment('Идентификатор');
            $table->uuid()->unique()->comment('UUID');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade')->comment('ID тенанта');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade')->comment('ID филиала');
            $table->string('correlation_id')->nullable()->index()->comment('ID корреляции');
            $table->string('name')->comment('Название блюда');
            $table->string('sku')->unique()->comment('SKU');
            $table->enum('diet_type', ['vegan', 'keto', 'protein', 'balanced', 'lowcarb'])->index()->comment('Тип диеты');
            $table->integer('calories')->comment('Килокалории');
            $table->integer('protein_g')->comment('Белки (г)');
            $table->integer('carbs_g')->comment('Углеводы (г)');
            $table->integer('fat_g')->comment('Жиры (г)');
            $table->integer('price')->comment('Цена (коп)');
            $table->integer('current_stock')->default(0)->comment('Остаток');
            $table->float('rating')->default(0)->comment('Рейтинг');
            $table->json('tags')->nullable()->comment('Теги');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'diet_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('healthy_foods');
    }
};
