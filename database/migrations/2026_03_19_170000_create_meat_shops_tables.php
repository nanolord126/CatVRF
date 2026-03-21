<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meat_shops')) return;

        Schema::create('meat_shops', function (Blueprint $table) {
            $table->id()->comment('Идентификатор');
            $table->uuid()->unique()->comment('UUID');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade')->comment('ID тенанта');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade')->comment('ID филиала');
            $table->string('correlation_id')->nullable()->index()->comment('ID корреляции');
            $table->string('name')->comment('Название продукта');
            $table->string('sku')->unique()->comment('SKU');
            $table->enum('meat_type', ['beef', 'pork', 'chicken', 'lamb', 'mixed'])->index()->comment('Тип мяса');
            $table->enum('cut', ['steak', 'fillet', 'ground', 'ribs', 'shoulder'])->comment('Вырез');
            $table->integer('weight_g')->comment('Вес (г)');
            $table->integer('price')->comment('Цена (коп)');
            $table->integer('current_stock')->default(0)->comment('Остаток');
            $table->boolean('is_certified')->default(true)->comment('Сертифицировано');
            $table->float('rating')->default(0)->comment('Рейтинг');
            $table->json('tags')->nullable()->comment('Теги');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'meat_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meat_shops');
    }
};
