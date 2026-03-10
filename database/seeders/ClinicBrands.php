<?php
namespace Database\Seeders;
class ClinicBrands extends BaseBrandSeeder {
    public function run(): void {
        $this->seedBrands('HumanClinics', [
            ['name' => 'Mayo Clinic', 'country' => 'USA'], ['name' => 'Cleveland Clinic', 'country' => 'USA'],
            ['name' => 'Johns Hopkins', 'country' => 'USA'], ['name' => 'Massachusetts General', 'country' => 'USA'],
            ['name' => 'Charité', 'country' => 'Germany'], ['name' => 'Guys and St Thomas', 'country' => 'UK'],
            ['name' => 'Mount Sinai', 'country' => 'USA'], ['name' => 'Stanford Health', 'country' => 'USA'],
            ['name' => 'UCLA Health', 'country' => 'USA'], ['name' => 'Cedars-Sinai', 'country' => 'USA'],
            ['name' => 'Bupa', 'country' => 'UK'], ['name' => 'Spire Healthcare', 'country' => 'UK'],
            ['name' => 'Ramsay Health', 'country' => 'Australia'], ['name' => 'IHH Healthcare', 'country' => 'Malaysia'],
            ['name' => 'Raffles Medical', 'country' => 'Singapore'], ['name' => 'Apollo Hospitals', 'country' => 'India'],
            ['name' => 'Fortis Healthcare', 'country' => 'India'], ['name' => 'Aster DM Healthcare', 'country' => 'UAE'],
            ['name' => 'Mediclinic', 'country' => 'South Africa'], ['name' => 'Sanitas', 'country' => 'Spain'],
            ['name' => 'Kaiser Permanente', 'country' => 'USA'], ['name' => 'HCA Healthcare', 'country' => 'USA'],
            ['name' => 'Fresenius Medical Care', 'country' => 'Germany'], ['name' => 'Roche', 'country' => 'Switzerland'],
            ['name' => 'Pfizer', 'country' => 'USA'], ['name' => 'Novartis', 'country' => 'Switzerland'],
            ['name' => 'Johnson & Johnson', 'country' => 'USA'], ['name' => 'Sanofi', 'country' => 'France'],
            ['name' => 'GlaxoSmithKline', 'country' => 'UK'], ['name' => 'AstraZeneca', 'country' => 'UK/Sweden'],
            ['name' => 'Merck', 'country' => 'USA'], ['name' => 'AbbVie', 'country' => 'USA'],
            ['name' => 'Amgen', 'country' => 'USA'], ['name' => 'Gilead Sciences', 'country' => 'USA'],
            ['name' => 'Bristol Myers Squibb', 'country' => 'USA'], ['name' => 'Eli Lilly', 'country' => 'USA'],
            ['name' => 'Novo Nordisk', 'country' => 'Denmark'], ['name' => 'Bayer Healthcare', 'country' => 'Germany'],
            ['name' => 'Takeda', 'country' => 'Japan'], ['name' => 'Terumo', 'country' => 'Japan'],
            ['name' => 'Olympus Medical', 'country' => 'Japan'], ['name' => 'Siemens Healthineers', 'country' => 'Germany'],
            ['name' => 'Philips Healthcare', 'country' => 'Netherlands'], ['name' => 'GE HealthCare', 'country' => 'USA'],
            ['name' => 'Medtronic', 'country' => 'USA/Ireland'], ['name' => 'Stryker', 'country' => 'USA'],
            ['name' => 'Boston Scientific', 'country' => 'USA'], ['name' => 'Baxter', 'country' => 'USA'],
            ['name' => 'Quest Diagnostics', 'country' => 'USA'],
            ['name' => 'Kot-Clinic (Partner)', 'country' => 'Russia', 'is_platform_partner' => true],
        ]);
    }
}


