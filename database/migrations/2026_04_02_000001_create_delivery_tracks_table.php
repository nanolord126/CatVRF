<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_tracks', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('delivery_order_id')->index();
            $table->unsignedBigInteger('courier_id')->index();
            $table->decimal('lat', 10, 8);
            $table->decimal('lon', 11, 8);
            $table->decimal('speed', 6, 2)->nullable()->comment('км/ч');
            $table->decimal('bearing', 6, 2)->nullable()->comment('направление 0-360');
            $table->string('correlation_id', 36)->nullable()->index();
            $table->timestamp('tracked_at')->useCurrent();

            $table->index(['delivery_order_id', 'tracked_at']);
            $table->index(['courier_id', 'tracked_at']);

            $table->foreign('delivery_order_id')
                ->references('id')->on('logistics_delivery_orders')
                ->onDelete('cascade');

            $table->foreign('courier_id')
                ->references('id')->on('couriers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_tracks');
    }
};
