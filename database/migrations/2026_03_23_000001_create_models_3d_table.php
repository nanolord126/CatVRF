<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class CreateModels3dTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('models_3d')) {
            return;
        }

        Schema::create('models_3d', static function (Blueprint $table): void {
            $table->id()->comment('Первичный ключ');
            $table->uuid()->unique()->index()->comment('UUID для публичного доступа');
            $table->unsignedBigInteger('tenant_id')->index()->comment('ID тенанта для изоляции');
            $table->unsignedBigInteger('business_group_id')->nullable()->index()->comment('ID филиала (если применимо)');
            $table->string('modelable_type', 255)->comment('Polymorphic relation type');
            $table->unsignedBigInteger('modelable_id')->comment('Polymorphic relation ID');
            $table->string('name', 255)->comment('Название 3D модели');
            $table->text('description')->nullable()->comment('Описание модели');
            $table->string('file_path', 500)->index()->comment('Путь к файлу в storage');
            $table->enum('model_type', ['glb', 'gltf', 'obj', 'fbx'])->comment('Тип 3D модели');
            $table->bigInteger('file_size')->comment('Размер файла в байтах (макс 50MB = 52428800)');
            $table->string('hash', 64)->unique()->index()->comment('SHA-256 хеш для дедупликации');
            $table->json('metadata')->nullable()->comment('Метаданные: scale, position, rotation, animation settings');
            $table->enum('status', ['uploading', 'processing', 'malware_scan', 'active', 'rejected', 'deleted'])->default('uploading')->index()->comment('Статус обработки модели');
            $table->text('rejection_reason')->nullable()->comment('Причина отклонения');
            $table->integer('download_count')->default(0)->comment('Количество скачиваний для аналитики');
            $table->integer('view_count')->default(0)->comment('Количество просмотров');
            $table->string('correlation_id', 36)->nullable()->index()->comment('ID для трейсинга операции');
            $table->json('tags')->nullable()->comment('JSONB теги для фильтрации и аналитики');
            $table->softDeletes()->comment('Мягкое удаление');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'status'], 'idx_models_3d_tenant_status');
            $table->index(['created_at'], 'idx_models_3d_created_at');

            $table->comment('Таблица 3D моделей для товаров, услуг, транспорта');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('models_3d');
    }
}
