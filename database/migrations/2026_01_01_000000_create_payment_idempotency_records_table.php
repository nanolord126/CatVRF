<?php declare(strict_types=1);





use Illuminate\Database\Migrations\Migration;


use Illuminate\Database\Schema\Blueprint;


use Illuminate\Support\Facades\Schema;





return new class extends Migration {


    public function up(): void


    {


        if (Schema::hasTable('payment_idempotency_records')) {


            return;


        }





        Schema::create('payment_idempotency_records', function (Blueprint $table) {


            $table->id();


            $table->string('operation', 64)->index()->comment('Тип операции (init_payment, capture, refund, payout)');


            $table->string('idempotency_key', 36)->unique()->index();


            $table->unsignedBigInteger('merchant_id')->index()->comment('ID мерчанта/tenant');


            $table->string('payload_hash', 64)->index()->comment('SHA256 хеш payload для быстрой проверки');


            $table->json('response_data')->nullable()->comment('Кэшированный ответ (если уже обработано)');


            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');


            $table->timestamp('expires_at')->comment('Когда запись удаляется (TTL 90 дней)');


            $table->string('correlation_id', 36)->nullable()->index();


            $table->timestamps();





            $table->comment('Запись для проверки идемпотентности платежей (защита от дублирования)');


            $table->index(['merchant_id', 'operation', 'created_at']);


        });


    }





    public function down(): void


    {


        Schema::dropIfExists('payment_idempotency_records');


    }


};




