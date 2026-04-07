<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Добавление B2B-полей в таблицу business_groups:
 *  - legal_name, kpp (могут уже быть — добавляем только если не существуют)
 *  - b2b_tier, credit_limit_kopecks, credit_used_kopecks, payment_term_days
 *  - bank_account, bank_name, bic
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_groups', static function (Blueprint $table): void {
            if (!Schema::hasColumn('business_groups', 'legal_name')) {
                $table->string('legal_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('business_groups', 'b2b_tier')) {
                $table->enum('b2b_tier', ['standard', 'silver', 'gold', 'platinum'])
                    ->default('standard')
                    ->after('commission_percent');
            }
            if (!Schema::hasColumn('business_groups', 'credit_limit_kopecks')) {
                $table->unsignedBigInteger('credit_limit_kopecks')->default(0)->after('b2b_tier');
            }
            if (!Schema::hasColumn('business_groups', 'credit_used_kopecks')) {
                $table->unsignedBigInteger('credit_used_kopecks')->default(0)->after('credit_limit_kopecks');
            }
            if (!Schema::hasColumn('business_groups', 'payment_term_days')) {
                $table->unsignedSmallInteger('payment_term_days')->default(14)->after('credit_used_kopecks');
            }
            if (!Schema::hasColumn('business_groups', 'bank_account')) {
                $table->string('bank_account', 20)->nullable()->after('payment_term_days');
            }
            if (!Schema::hasColumn('business_groups', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('bank_account');
            }
            if (!Schema::hasColumn('business_groups', 'bic')) {
                $table->string('bic', 9)->nullable()->after('bank_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('business_groups', static function (Blueprint $table): void {
            $cols = ['legal_name', 'b2b_tier', 'credit_limit_kopecks', 'credit_used_kopecks',
                'payment_term_days', 'bank_account', 'bank_name', 'bic'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('business_groups', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
