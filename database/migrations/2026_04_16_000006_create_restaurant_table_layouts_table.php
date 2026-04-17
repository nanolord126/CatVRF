<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_table_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->string('layout_name');
            $table->integer('floor_number')->default(1);
            $table->json('layout_json');
            $table->json('dimensions');
            $table->boolean('is_active')->default(true);
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            
            $table->index(['tenant_id', 'restaurant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_table_layouts');
    }
};
