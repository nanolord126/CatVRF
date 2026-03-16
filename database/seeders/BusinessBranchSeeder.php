<?php  
namespace Database\Seeders;
use App\Models\BusinessBranch;
use Illuminate\Database\Seeder;  

final class BusinessBranchSeeder extends Seeder {     public function run(): void     {         BusinessBranch::factory()->count(20)->create();         BusinessBranch::factory()->count(10)->active()->create();     } }