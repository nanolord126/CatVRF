<?php  declare(strict_types=1);


namespace Database\Seeders;
use App\Models\Tenants\Clinic;
use Illuminate\Database\Seeder;  final 

final class ClinicSeeder extends Seeder { 	public function run(): void 	{ 		Clinic::create([ 			'name' => 'City Medical Center', 			'type' => 'human', 			'address' => '789 Health Street, Medical District', 			'phone' => '+1-555-0101', 			'email' => 'info@citymedical.com', 			'geo_lat' => 40.7128, 			'geo_lng' => -74.0060, 		]);  		Clinic::create([ 			'name' => 'Wellness Clinic', 			'type' => 'human', 			'address' => '456 Wellness Avenue, Health Zone', 			'phone' => '+1-555-0102', 			'email' => 'contact@wellnessclinic.com', 			'geo_lat' => 40.7580, 			'geo_lng' => -73.9855, 		]);  		Clinic::create([ 			'name' => 'Emergency Care Hospital', 			'type' => 'human', 			'address' => '123 Emergency Road, Hospital District', 			'phone' => '+1-555-0103', 			'email' => 'emergency@carehospital.com', 			'geo_lat' => 40.6892, 			'geo_lng' => -74.0445, 		]); 	} }