<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fashion_influencer_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fashion_product_id')->constrained('fashion_products')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('platform');
            $table->string('influencer_handle');
            $table->timestamp('mentioned_at');
            $table->timestamps();
            
            $table->index(['fashion_product_id', 'tenant_id']);
            $table->index(['platform', 'influencer_handle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fashion_influencer_mentions');
    }
};
