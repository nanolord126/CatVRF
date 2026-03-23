<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class CreateHeatmapSnapshotsTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('heatmap_snapshots')) {
            return;
        }

        Schema::create('heatmap_snapshots', static function (Blueprint $table): void {
            $table->id()->comment('Первичный ключ');
            $table->uuid()->unique()->indexed()->comment('UUID снимка');
            $table->unsignedBigInteger('tenant_id')->nullable()->indexed()->comment('ID тенанта');
            $table->enum('heatmap_type', ['geo', 'click', 'time', 'vertical'])->indexed()->comment('Тип тепловой карты');
            $table->string('vertical', 100)->nullable()->comment('Вертикаль (auto, beauty, food)');
            $table->date('snapshot_date')->indexed()->comment('Дата снимка');
            $table->json('data')->comment('Сжатые данные точек тепловой карты');
            $table->string('file_path')->nullable()->comment('Путь к PNG/PDF экспорту');
            $table->enum('status', ['generating', 'ready', 'failed'])->default('generating')->comment('Статус генерации');
            $table->unsignedInteger('data_points_count')->comment('Количество точек данных');
            $table->string('correlation_id', 36)->nullable()->indexed()->comment('ID для трейсинга');
            $table->timestamps();

            $table->index(['tenant_id', 'heatmap_type', 'snapshot_date'], 'idx_snapshots_composite');

            $table->comment('Кэшированные снимки тепловых карт для быстрой загрузки');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heatmap_snapshots');
    }
}
