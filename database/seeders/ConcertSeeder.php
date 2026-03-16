<?php  declare(strict_types=1);


namespace Database\Seeders;
use App\Models\Tenants\Concert;
use Illuminate\Database\Seeder;  final 

final class ConcertSeeder extends Seeder { 	public function run(): void 	{ 		Concert::create([ 			'name' => 'Classical Night Symphony', 			'description' => 'An evening of classical masterpieces performed by the National Orchestra', 			'date' => now()->addDays(30)->toDateString(), 			'time' => '19:00', 			'venue' => 'Grand Concert Hall', 			'address' => '123 Music Street, Downtown', 			'price' => 45.00, 			'capacity' => 1000, 			'status' => 'active', 		]);  		Concert::create([ 			'name' => 'Jazz Improvisation Session', 			'description' => 'Live jazz performance featuring local and international artists', 			'date' => now()->addDays(45)->toDateString(), 			'time' => '20:00', 			'venue' => 'Blue Note Jazz Club', 			'address' => '456 Jazz Avenue, Arts District', 			'price' => 35.00, 			'capacity' => 300, 			'status' => 'active', 		]);  		Concert::create([ 			'name' => 'Pop Music Festival', 			'description' => 'Three-day music festival featuring top pop artists', 			'date' => now()->addDays(60)->toDateString(), 			'time' => '18:00', 			'venue' => 'Central Park Amphitheater', 			'address' => 'Central Park, Main City', 			'price' => 150.00, 			'capacity' => 5000, 			'status' => 'active', 		]); 	} }