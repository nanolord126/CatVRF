<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->comment('Движения товара: входящие, исходящие, перемещения.');
                $table->id();
                $table->unsignedBigInteger('item_id')->comment('ID товара');
                $table->integer('quantity')->comment('Количество');
                $table->string('type')->comment('Тип: in, out, transfer');
                $table->timestamps();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('stock_movements'); }
};
