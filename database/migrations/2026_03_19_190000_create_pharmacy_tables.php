<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pharmacies')) return;

        Schema::create('pharmacies', function (Blueprint $table) {
            $table->id()->comment('Идентификатор');
            $table->uuid()->unique()->comment('UUID');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade')->comment('ID тенанта');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade')->comment('ID филиала');
            $table->string('correlation_id')->nullable()->index()->comment('ID корреляции');
            $table->string('name')->comment('Название лекарства');
            $table->string('sku')->unique()->comment('SKU');
            $table->string('mnn')->comment('МНН');
            $table->enum('form', ['tablet', 'capsule', 'syrup', 'drops', 'ointment', 'injection'])->index()->comment('Форма');
            $table->string('dosage')->comment('Дозировка');
            $table->integer('price')->comment('Цена (коп)');
            $table->integer('current_stock')->default(0)->comment('Остаток');
            $table->boolean('is_otc')->default(false)->comment('Безрецептурный');
            $table->boolean('requires_prescription')->default(false)->comment('Требует рецепт');
            $table->float('rating')->default(0)->comment('Рейтинг');
            $table->json('tags')->nullable()->comment('Теги');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'mnn']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacies');
    }
};
