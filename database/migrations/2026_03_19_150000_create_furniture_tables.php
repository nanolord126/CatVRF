<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('furnitures')) return;

        Schema::create('furnitures', function (Blueprint $table) {
            $table->id()->comment('Идентификатор');
            $table->uuid()->unique()->comment('UUID мебели');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade')->comment('ID тенанта');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade')->comment('ID филиала');
            $table->string('correlation_id')->nullable()->index()->comment('ID корреляции');
            $table->string('name')->comment('Название товара');
            $table->string('sku')->unique()->comment('SKU');
            $table->enum('category', ['sofa', 'chair', 'table', 'bed', 'cabinet', 'shelf'])->index()->comment('Категория');
            $table->enum('material', ['wood', 'metal', 'leather', 'fabric', 'ceramic'])->comment('Материал');
            $table->integer('price')->comment('Цена (коп)');
            $table->integer('current_stock')->default(0)->comment('Остаток');
            $table->float('rating')->default(0)->comment('Рейтинг');
            $table->boolean('is_available')->default(true)->comment('Доступно для заказа');
            $table->json('tags')->nullable()->comment('Теги');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('furnitures');
    }
};
