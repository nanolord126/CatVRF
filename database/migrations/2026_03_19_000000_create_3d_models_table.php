<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('3d_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->indexed();
            $table->uuid('correlation_id')->nullable()->indexed();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            $table->foreignId('jewelry_item_id')->constrained('jewelry_items')->onDelete('cascade');
            
            $table->string('model_url')->comment('URL to 3D model file (GLB/GLTF)');
            $table->string('texture_url')->nullable()->comment('URL to texture file');
            $table->string('material_type')->default('gold')->comment('Metal type: gold, silver, platinum, rose_gold');
            $table->json('dimensions')->nullable()->comment('3D dimensions: width, height, depth');
            $table->decimal('weight_grams', 8, 2)->nullable()->comment('Weight in grams');
            $table->string('preview_image_url')->nullable()->comment('Preview image URL');
            
            $table->boolean('ar_compatible')->default(true)->comment('Is AR compatible');
            $table->boolean('vr_compatible')->default(true)->comment('Is VR compatible');
            $table->decimal('file_size_mb', 10, 2)->nullable()->comment('File size in MB');
            $table->string('format')->default('glb')->comment('File format: glb, gltf, usdz, obj');
            
            $table->enum('status', ['uploaded', 'processing', 'active', 'archived'])->default('active');
            $table->json('tags')->nullable()->comment('Tags for search and analytics');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->comment('3D models for jewelry items with AR/VR support');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('3d_models');
    }
};
