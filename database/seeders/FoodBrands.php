<?php
namespace Database\Seeders;
class FoodBrands extends BaseBrandSeeder {
    public function run(): void {
        $this->seedBrands('FoodDelivery', [
            ['name' => 'Nestlé', 'country' => 'Switzerland'], ['name' => 'PepsiCo', 'country' => 'USA'],
            ['name' => 'Coca-Cola', 'country' => 'USA'], ['name' => 'Unilever', 'country' => 'UK/Netherlands'],
            ['name' => 'Mars', 'country' => 'USA'], ['name' => 'Mondelez', 'country' => 'USA'],
            ['name' => 'Danone', 'country' => 'France'], ['name' => 'General Mills', 'country' => 'USA'],
            ['name' => 'Kraft Heinz', 'country' => 'USA'], ['name' => 'Kellogg\'s', 'country' => 'USA'],
            ['name' => 'Ferrero', 'country' => 'Italy'], ['name' => 'Lindt', 'country' => 'Switzerland'],
            ['name' => 'McDonald\'s', 'country' => 'USA'], ['name' => 'Starbucks', 'country' => 'USA'],
            ['name' => 'KFC', 'country' => 'USA'], ['name' => 'Burger King', 'country' => 'USA'],
            ['name' => 'Subway', 'country' => 'USA'], ['name' => 'Pizza Hut', 'country' => 'USA'],
            ['name' => 'Domino\'s', 'country' => 'USA'], ['name' => 'Tyson Foods', 'country' => 'USA'],
            ['name' => 'Lactalis', 'country' => 'France'], ['name' => 'Arla Foods', 'country' => 'Denmark'],
            ['name' => 'FrieslandCampina', 'country' => 'Netherlands'], ['name' => 'Fonterra', 'country' => 'New Zealand'],
            ['name' => 'Heineken', 'country' => 'Netherlands'], ['name' => 'AB InBev', 'country' => 'Belgium'],
            ['name' => 'Diageo', 'country' => 'UK'], ['name' => 'Pernod Ricard', 'country' => 'France'],
            ['name' => 'Bacardi', 'country' => 'Bermuda'], ['name' => 'Red Bull', 'country' => 'Austria'],
            ['name' => 'Monster Beverage', 'country' => 'USA'], ['name' => 'McCormick', 'country' => 'USA'],
            ['name' => 'Barry Callebaut', 'country' => 'Switzerland'], ['name' => 'JBS', 'country' => 'Brazil'],
            ['name' => 'Cargill', 'country' => 'USA'], ['name' => 'ADM', 'country' => 'USA'],
            ['name' => 'Wilmar International', 'country' => 'Singapore'], ['name' => 'Olam Group', 'country' => 'Singapore'],
            ['name' => 'Barilla', 'country' => 'Italy'], ['name' => 'Campari', 'country' => 'Italy'],
            ['name' => 'Lavazza', 'country' => 'Italy'], ['name' => 'Illy', 'country' => 'Italy'],
            ['name' => 'Yakult', 'country' => 'Japan'], ['name' => 'Nissin Foods', 'country' => 'Japan'],
            ['name' => 'Ajinomoto', 'country' => 'Japan'], ['name' => 'Meiji', 'country' => 'Japan'],
            ['name' => 'Indofood', 'country' => 'Indonesia'], ['name' => 'CP Foods', 'country' => 'Thailand'],
            ['name' => 'Thai Union', 'country' => 'Thailand'],
            ['name' => 'Kot-Food (Partner)', 'country' => 'Russia', 'is_platform_partner' => true],
        ]);
    }
}


