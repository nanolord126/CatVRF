<?php  
namespace Database\Seeders;
use App\Models\PaymentTransaction;
use Illuminate\Database\Seeder;  

final class PaymentTransactionSeeder extends Seeder {     public function run(): void     {         PaymentTransaction::factory()->count(25)->create();         PaymentTransaction::factory()->count(12)->completed()->create();         PaymentTransaction::factory()->count(5)->pending()->create();         PaymentTransaction::factory()->count(3)->failed()->create();     } }
