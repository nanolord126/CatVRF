<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bouquets')) return;

        Schema::create('bouquets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('shop_id')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->json('flowers_composition');
            $table->decimal('price', 10, 2);
            $table->json('consumables_json')->nullable();
            $table->boolean('is_available')->default(true);
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->comment('Букеты и композиции');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bouquets');
    }
};
