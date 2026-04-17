<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('real_estate_property_documents')) {
            return;
        }

        Schema::create('real_estate_property_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('property_id')->index();
            $table->string('url', 2048)->comment('URL документа (CDN или storage)');
            $table->string('name', 255)->comment('Название документа (например: Свидетельство о праве)');
            $table->string('doc_type', 100)->nullable()->comment('Тип: title_deed|floor_plan|inspection|other');

            $table->foreign('property_id')
                ->references('id')
                ->on('real_estate_properties')
                ->cascadeOnDelete();

            $table->index(['property_id', 'doc_type'], 'real_estate_docs_property_type_idx');
        });

        // SQLite doesn't support table comments via ALTER TABLE
        if (config('database.default') !== 'sqlite') {
            \Illuminate\Support\Facades\DB::statement(
                "ALTER TABLE real_estate_property_documents COMMENT = 'Документы объектов недвижимости'"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_property_documents');
    }
};
