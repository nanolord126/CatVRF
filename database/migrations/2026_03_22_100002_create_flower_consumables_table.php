<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('flower_consumables')) return;

        Schema::create('flower_consumables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('shop_id')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->enum('type', ['ribbon', 'wrapping', 'card', 'vase']);
            $table->integer('current_stock')->default(0);
            $table->integer('min_stock_threshold')->default(10);
            $table->string('unit');
            $table->decimal('price_per_unit', 10, 2);
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->comment('Расходники для букетов');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flower_consumables');
    }
};
