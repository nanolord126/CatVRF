<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('perfumes')) return;

        Schema::create('perfumes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('shop_id')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('brand');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('fragrance_notes')->nullable();
            $table->integer('volume_ml');
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->boolean('is_available')->default(true);
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->comment('Парфюмерия');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfumes');
    }
};
