<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Добавляет юридические поля в таблицу tenants (kpp, ogrn, legal_entity_type и т.д.)
 * Каждая колонка добавляется независимо (idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenants') === false) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'kpp')) {
                $table->string('kpp')->nullable()->after('inn');
            }
            if (! Schema::hasColumn('tenants', 'ogrn')) {
                $table->string('ogrn')->nullable()->after('kpp');
            }
            if (! Schema::hasColumn('tenants', 'legal_entity_type')) {
                $table->string('legal_entity_type')->nullable()->after('ogrn');
            }
            if (! Schema::hasColumn('tenants', 'legal_address')) {
                $table->text('legal_address')->nullable()->after('legal_entity_type');
            }
            if (! Schema::hasColumn('tenants', 'actual_address')) {
                $table->text('actual_address')->nullable()->after('legal_address');
            }
            if (! Schema::hasColumn('tenants', 'phone')) {
                $table->string('phone')->nullable()->after('actual_address');
            }
            if (! Schema::hasColumn('tenants', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('tenants', 'website')) {
                $table->string('website')->nullable()->after('email');
            }
            if (! Schema::hasColumn('tenants', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('website');
            }
            if (! Schema::hasColumn('tenants', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('tenants', 'meta')) {
                $table->json('meta')->nullable()->after('is_verified');
            }
            if (! Schema::hasColumn('tenants', 'tags')) {
                $table->json('tags')->nullable()->after('meta');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        $cols = ['tags', 'meta', 'is_verified', 'is_active', 'website', 'email',
                 'phone', 'actual_address', 'legal_address', 'legal_entity_type',
                 'ogrn', 'kpp'];

        Schema::table('tenants', function (Blueprint $table) use ($cols) {
            foreach ($cols as $col) {
                if (Schema::hasColumn('tenants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
