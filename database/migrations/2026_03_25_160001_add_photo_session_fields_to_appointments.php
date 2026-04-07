<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Миграция для расширения модели Appointment полями фотосессий (Вертикаль Beauty 2026).
     */
    public function up(): void
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (!Schema::hasColumn('appointments', 'is_photo_session')) {
                    $table->boolean('is_photo_session')->default(false)->after('is_wedding_event');
                    $table->string('photo_session_type')->nullable()->after('is_photo_session');
                    $table->unsignedBigInteger('photographer_id')->nullable()->index()->after('photo_session_type');
                    $table->string('photo_location')->nullable()->after('photographer_id');
                    $table->text('photo_concept')->nullable()->after('photo_location');
                    
                    $table->comment('Расширение для фотосессий в вертикали Beauty');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn([
                    'is_photo_session',
                    'photo_session_type',
                    'photographer_id',
                    'photo_location',
                    'photo_concept',
                ]);
            });
        }
    }
};


