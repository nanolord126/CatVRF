<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_menu_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->json('analysis_data');
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            
            $table->unique('restaurant_id');
            $table->index(['tenant_id', 'restaurant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_menu_analyses');
    }
};
