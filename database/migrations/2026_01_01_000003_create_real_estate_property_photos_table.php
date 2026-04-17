<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('real_estate_property_photos')) {
            return;
        }

        Schema::create('real_estate_property_photos', function (Blueprint $table): void {
            $table->id();
            $table->uuid('property_id')->index();
            $table->string('url', 2048)->comment('URL фотографии (CDN или storage)');
            $table->string('caption', 255)->nullable()->comment('Подпись к фото');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Порядок сортировки');

            $table->foreign('property_id')
                ->references('id')
                ->on('real_estate_properties')
                ->cascadeOnDelete();

            $table->index(['property_id', 'sort_order'], 'real_estate_photos_property_sort_idx');
        });

        // SQLite doesn't support table comments via ALTER TABLE
        if (config('database.default') !== 'sqlite') {
            \Illuminate\Support\Facades\DB::statement(
                "ALTER TABLE real_estate_property_photos COMMENT = 'Фотографии объектов недвижимости'"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_property_photos');
    }
};
