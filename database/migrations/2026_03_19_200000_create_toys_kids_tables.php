<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('toys_kids')) return;

        Schema::create('toys_kids', function (Blueprint $table) {
            $table->id()->comment('Идентификатор');
            $table->uuid()->unique()->comment('UUID');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade')->comment('ID тенанта');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade')->comment('ID филиала');
            $table->string('correlation_id')->nullable()->index()->comment('ID корреляции');
            $table->string('name')->comment('Название игрушки');
            $table->string('sku')->unique()->comment('SKU');
            $table->enum('category', ['puzzle', 'plush', 'building', 'vehicle', 'board_game', 'outdoor'])->index()->comment('Категория');
            $table->integer('age_min')->comment('Возраст от');
            $table->integer('age_max')->comment('Возраст до');
            $table->integer('price')->comment('Цена (коп)');
            $table->integer('current_stock')->default(0)->comment('Остаток');
            $table->float('rating')->default(0)->comment('Рейтинг');
            $table->json('tags')->nullable()->comment('Теги');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toys_kids');
    }
};
