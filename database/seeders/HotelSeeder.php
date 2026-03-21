<?php  declare(strict_types=1);


namespace Database\Seeders;
use App\Models\Tenants\Hotel;
use Illuminate\Database\Seeder;  final 

final class HotelSeeder extends Seeder { 	public function run(): void 	{ 		Hotel::create([ 			'name' => 'Grand Luxury Hotel', 			'address' => '100 Prestigious Avenue, Downtown', 			'phone' => '+1-555-0401', 			'email' => 'info@grandluxury.com', 			'geo_lat' => 40.7580, 			'geo_lng' => -73.9855, 			'star_rating' => 5, 			'amenities' => json_encode(['restaurant', 'spa', 'pool', 'concierge', 'room_service']), 			'status' => 'active', 		]);  		Hotel::create([ 			'name' => 'Comfort Inn & Suites', 			'address' => '250 Business Street, Corporate Zone', 			'phone' => '+1-555-0402', 			'email' => 'contact@comfortinn.com', 			'geo_lat' => 40.7489, 			'geo_lng' => -73.9680, 			'star_rating' => 3, 			'amenities' => json_encode(['wifi', 'breakfast', 'gym', 'parking']), 			'status' => 'active', 		]);  		Hotel::create([ 			'name' => 'Beach Paradise Resort', 			'address' => '500 Coastal Highway, Beach Area', 			'phone' => '+1-555-0403', 			'email' => 'stay@beachparadise.com', 			'geo_lat' => 40.5731, 			'geo_lng' => -73.9730, 			'star_rating' => 4, 			'amenities' => json_encode(['beach_access', 'water_sports', 'entertainment', 'dining', 'spa']), 			'status' => 'active', 		]); 	} }