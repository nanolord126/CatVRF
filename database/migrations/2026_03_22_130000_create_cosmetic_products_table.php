<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cosmetic_products')) return;

        Schema::create('cosmetic_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('salon_id')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('brand');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->string('volume')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_professional')->default(false);
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->comment('Косметика для продажи в салонах');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cosmetic_products');
    }
};
