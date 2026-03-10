<?php
namespace Database\Seeders;
class ConstructionBrands extends BaseBrandSeeder {
    public function run(): void {
        $this->seedBrands('Construction', [
            ['name' => 'Caterpillar', 'country' => 'USA'], ['name' => 'Komatsu', 'country' => 'Japan'],
            ['name' => 'Deere & Company', 'country' => 'USA'], ['name' => 'BHP Group', 'country' => 'Australia'],
            ['name' => 'Rio Tinto', 'country' => 'UK/Australia'], ['name' => 'LafargeHolcim', 'country' => 'Switzerland'],
            ['name' => 'Saint-Gobain', 'country' => 'France'], ['name' => 'CRH', 'country' => 'Ireland'],
            ['name' => 'Heidelberg Materials', 'country' => 'Germany'], ['name' => 'BASF (Construction)', 'country' => 'Germany'],
            ['name' => 'Sika', 'country' => 'Switzerland'], ['name' => 'Knauf', 'country' => 'Germany'],
            ['name' => 'Hilti', 'country' => 'Liechtenstein'], ['name' => 'Stanley Black & Decker', 'country' => 'USA'],
            ['name' => 'DeWalt', 'country' => 'USA'], ['name' => 'Makita', 'country' => 'Japan'],
            ['name' => 'Bosch Power Tools', 'country' => 'Germany'], ['name' => 'Milwaukee Tool', 'country' => 'USA'],
            ['name' => 'Ryobi', 'country' => 'Japan'], ['name' => '3M', 'country' => 'USA'],
            ['name' => 'Owens Corning', 'country' => 'USA'], ['name' => 'Sherwin-Williams', 'country' => 'USA'],
            ['name' => 'PPG Industries', 'country' => 'USA'], ['name' => 'AkzoNobel', 'country' => 'Netherlands'],
            ['name' => 'Assa Abloy', 'country' => 'Sweden'], ['name' => 'Schindler', 'country' => 'Switzerland'],
            ['name' => 'Otis Worldwide', 'country' => 'USA'], ['name' => 'Kone', 'country' => 'Finland'],
            ['name' => 'Thyssenkrupp', 'country' => 'Germany'], ['name' => 'Tata Steel', 'country' => 'India'],
            ['name' => 'ArcelorMittal', 'country' => 'Luxembourg'], ['name' => 'Nippon Steel', 'country' => 'Japan'],
            ['name' => 'Baosteel', 'country' => 'China'], ['name' => 'CEMEX', 'country' => 'Mexico'],
            ['name' => 'Boral', 'country' => 'Australia'], ['name' => 'James Hardie', 'country' => 'Ireland/USA'],
            ['name' => 'Tarkett', 'country' => 'France'], ['name' => 'Armstrong World', 'country' => 'USA'],
            ['name' => 'Kohler', 'country' => 'USA'], ['name' => 'TOTO', 'country' => 'Japan'],
            ['name' => 'Lixil Group', 'country' => 'Japan'], ['name' => 'Grohe', 'country' => 'Germany'],
            ['name' => 'Hansgrohe', 'country' => 'Germany'], ['name' => 'Villeroy & Boch', 'country' => 'Germany'],
            ['name' => 'Legrand', 'country' => 'France'], ['name' => 'Schneider Electric', 'country' => 'France'],
            ['name' => 'ABB', 'country' => 'Switzerland/Sweden'], ['name' => 'Siemens (Building)', 'country' => 'Germany'],
            ['name' => 'Honeywell', 'country' => 'USA'],
            ['name' => 'Kot-Build (Partner)', 'country' => 'Russia', 'is_platform_partner' => true],
        ]);
    }
}


