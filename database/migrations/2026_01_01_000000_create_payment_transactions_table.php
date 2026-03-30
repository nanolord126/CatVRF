<?php declare(strict_types=1);





use Illuminate\Database\Migrations\Migration;


use Illuminate\Database\Schema\Blueprint;


use Illuminate\Support\Facades\Schema;





return new class extends Migration {


    public function up(): void


    {


        if (Schema::hasTable('payment_transactions')) {


            return;


        }





        Schema::create('payment_transactions', function (Blueprint $table) {


            $table->id();


            $table->uuid('uuid')->nullable()->unique()->index();
            $table->unsignedBigInteger('wallet_id')->nullable()->index();


            $table->unsignedBigInteger('tenant_id')->index();


            $table->unsignedBigInteger('user_id')->nullable()->index();


            $table->string('idempotency_key', 36)->unique()->index()->comment('Ключ идемпотентности');


            $table->string('provider_code', 64)->index()->comment('Код платёжного шлюза (tinkoff, sber, tochka)');


            $table->string('provider_payment_id', 128)->nullable()->unique()->comment('ID платежа на стороне шлюза');


            $table->enum('status', ['pending', 'authorized', 'captured', 'refunded', 'failed', 'cancelled'])->default('pending')->index();


            $table->bigInteger('amount')->comment('Сумма платежа (копейки)');


            $table->string('currency', 3)->default('RUB');


            $table->bigInteger('hold_amount')->nullable()->comment('Сумма на холде (копейки)');


            $table->boolean('hold')->default(false)->comment('Использовать ли холд вместо capture');


            $table->timestamp('authorized_at')->nullable();


            $table->timestamp('captured_at')->nullable();


            $table->timestamp('refunded_at')->nullable();


            $table->timestamp('failed_at')->nullable();


            $table->string('correlation_id', 36)->nullable()->index();


            $table->string('ip_address', 45)->nullable()->comment('Client IP address (IPv4/IPv6)');


            $table->string('device_fingerprint')->nullable()->comment('Device fingerprint for fraud detection');


            $table->float('fraud_score', 3, 2)->nullable()->comment('ML-оценка фрода (0-1)');


            $table->string('fraud_ml_version', 32)->nullable()->comment('Версия ML-модели');


            $table->boolean('three_ds_required')->default(false);


            $table->boolean('three_ds_verified')->default(false);


            $table->json('metadata')->nullable()->comment('Доп информация (description, items, etc.)');


            $table->json('tags')->nullable();


            $table->timestamps();





            $table->comment('Журнал платежных транзакций');


            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');


            $table->index(['tenant_id', 'status', 'created_at']);


            $table->index(['user_id', 'status']);


        });


    }





    public function down(): void


    {


        Schema::dropIfExists('payment_transactions');


    }


};


