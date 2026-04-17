<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('order_id')->constrained('restaurant_orders')->onDelete('cascade');
            $table->string('old_status');
            $table->string('new_status');
            $table->json('metadata')->nullable();
            $table->timestamp('changed_at');
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            
            $table->index(['order_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_order_status_history');
    }
};
