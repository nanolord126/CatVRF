<?php
namespace Database\Seeders;
class VetBrands extends BaseBrandSeeder {
    public function run(): void {
        $this->seedBrands('VetClinics', [
            ['name' => 'VCA Animal Hospitals', 'country' => 'USA'], ['name' => 'Banfield Pet Hospital', 'country' => 'USA'],
            ['name' => 'BluePearl Specialty', 'country' => 'USA'], ['name' => 'Petco Love', 'country' => 'USA'],
            ['name' => 'Mars Veterinary Health', 'country' => 'Global'], ['name' => 'IVC Evidensia', 'country' => 'Europe'],
            ['name' => 'CVS Group', 'country' => 'UK'], ['name' => 'Linnaeus', 'country' => 'UK'],
            ['name' => 'AniCura', 'country' => 'Europe'], ['name' => 'Greencross Vets', 'country' => 'Australia'],
            ['name' => 'Zoetis', 'country' => 'USA'], ['name' => 'Boehringer Ingelheim Animal', 'country' => 'Germany'],
            ['name' => 'Merck Animal Health', 'country' => 'USA'], ['name' => 'Elanco', 'country' => 'USA'],
            ['name' => 'IDEXX Laboratories', 'country' => 'USA'], ['name' => 'Heska', 'country' => 'USA'],
            ['name' => 'Covetrus', 'country' => 'USA'], ['name' => 'Virbac', 'country' => 'France'],
            ['name' => 'Ceva Santé Animale', 'country' => 'France'], ['name' => 'Vetoquinol', 'country' => 'France'],
            ['name' => 'Royal Canin', 'country' => 'France'], ['name' => 'Hill\'s Pet Nutrition', 'country' => 'USA'],
            ['name' => 'Purina Pro Plan', 'country' => 'USA'], ['name' => 'Blue Buffalo', 'country' => 'USA'],
            ['name' => 'Iams', 'country' => 'USA'], ['name' => 'Eukanuba', 'country' => 'USA'],
            ['name' => 'Nutro', 'country' => 'USA'], ['name' => 'Pedigree', 'country' => 'USA'],
            ['name' => 'Whiskas', 'country' => 'USA'], ['name' => 'Feliway', 'country' => 'France'],
            ['name' => 'Adaptil', 'country' => 'France'], ['name' => 'Frontline', 'country' => 'France'],
            ['name' => 'NexGard', 'country' => 'USA'], ['name' => 'Bravecto', 'country' => 'USA'],
            ['name' => 'Simparica', 'country' => 'USA'], ['name' => 'Seresto', 'country' => 'Germany'],
            ['name' => 'Kong Company', 'country' => 'USA'], ['name' => 'Trixie', 'country' => 'Germany'],
            ['name' => 'Ferplast', 'country' => 'Italy'], ['name' => 'Hagen', 'country' => 'Canada'],
            ['name' => 'JW Pet', 'country' => 'USA'], ['name' => 'Chuckit!', 'country' => 'USA'],
            ['name' => 'Kurgo', 'country' => 'USA'], ['name' => 'Ruffwear', 'country' => 'USA'],
            ['name' => 'Orijen', 'country' => 'Canada'], ['name' => 'Acana', 'country' => 'Canada'],
            ['name' => 'Hartz', 'country' => 'USA'], ['name' => 'Sentry', 'country' => 'USA'],
            ['name' => 'Zodiac Pet', 'country' => 'USA'],
            ['name' => 'Kot-Vet (Partner)', 'country' => 'Russia', 'is_platform_partner' => true],
        ]);
    }
}


