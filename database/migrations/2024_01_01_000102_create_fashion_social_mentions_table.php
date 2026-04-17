<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fashion_social_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fashion_product_id')->constrained('fashion_products')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('platform'); // instagram, tiktok, pinterest
            $table->string('post_url')->nullable();
            $table->integer('mentions_count')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            
            $table->index(['fashion_product_id', 'platform']);
            $table->index(['tenant_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fashion_social_mentions');
    }
};
