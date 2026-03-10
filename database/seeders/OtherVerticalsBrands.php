<?php
namespace Database\Seeders;
class OtherVerticalsBrands extends BaseBrandSeeder {
    public function run(): void {
        $this->seedBrands('TaxiAuto', [
            ['name' => 'Lyft', 'country' => 'USA'], ['name' => 'Didi Chuxing', 'country' => 'China'],
            ['name' => 'Grab', 'country' => 'Singapore'], ['name' => 'Gojek', 'country' => 'Indonesia'],
            ['name' => 'Bolt', 'country' => 'Estonia'], ['name' => 'Free Now', 'country' => 'Germany'],
            ['name' => 'Cabify', 'country' => 'Spain'], ['name' => 'BlaBlaCar', 'country' => 'France'],
            ['name' => 'Wheely', 'country' => 'UK/Russia'], ['name' => 'Gett', 'country' => 'Israel'],
            ['name' => 'Hertz', 'country' => 'USA'], ['name' => 'Avis Budget', 'country' => 'USA'],
            ['name' => 'Enterprise', 'country' => 'USA'], ['name' => 'Sixt', 'country' => 'Germany'],
            ['name' => 'Europcar', 'country' => 'France'], ['name' => 'Zipcar', 'country' => 'USA'],
            ['name' => 'Turo', 'country' => 'USA'], ['name' => 'Getaround', 'country' => 'USA'],
            ['name' => 'Careem', 'country' => 'UAE'], ['name' => 'Ola Cabs', 'country' => 'India'],
            ['name' => 'Beat', 'country' => 'Greece'], ['name' => 'Via', 'country' => 'USA'],
            ['name' => 'Lime', 'country' => 'USA'], ['name' => 'Bird', 'country' => 'USA'],
            ['name' => 'Tier Mobility', 'country' => 'Germany'], ['name' => 'Voi Technology', 'country' => 'Sweden'],
            ['name' => 'Whoosh', 'country' => 'Russia'], ['name' => 'Urent', 'country' => 'Russia'],
            ['name' => 'Citymobil', 'country' => 'Russia'], ['name' => 'Maxim', 'country' => 'Russia'],
            ['name' => 'Taxi 068', 'country' => 'Russia'], ['name' => 'Troika', 'country' => 'Russia'],
            ['name' => 'Moia', 'country' => 'Germany'], ['name' => 'Waymo', 'country' => 'USA'],
            ['name' => 'Cruise', 'country' => 'USA'], ['name' => 'Zoox', 'country' => 'USA'],
            ['name' => 'AutoX', 'country' => 'China'], ['name' => 'Pony.ai', 'country' => 'USA/China'],
            ['name' => 'Yandex Drive', 'country' => 'Russia'], ['name' => 'Delimobil', 'country' => 'Russia'],
            ['name' => 'BelkaCar', 'country' => 'Russia'], ['name' => 'Citydrive', 'country' => 'Russia'],
            ['name' => 'Localiza', 'country' => 'Brazil'], ['name' => 'Shouqi', 'country' => 'China'],
            ['name' => 'Caocao', 'country' => 'China'], ['name' => 'T3 Mobility', 'country' => 'China'],
            ['name' => '99', 'country' => 'Brazil'],
            ['name' => 'Kot-Taxi (Partner)', 'country' => 'Russia', 'is_platform_partner' => true],
        ]);

        $this->seedBrands('Events', [
            ['name' => 'Live Nation', 'country' => 'USA'], ['name' => 'AEG Presents', 'country' => 'USA'],
            ['name' => 'Ticketmaster', 'country' => 'USA'], ['name' => 'Eventbrite', 'country' => 'USA'],
            ['name' => 'Cvent', 'country' => 'USA'], ['name' => 'Reed Exhibitions', 'country' => 'UK'],
            ['name' => 'Informa', 'country' => 'UK'], ['name' => 'Messe Frankfurt', 'country' => 'Germany'],
            ['name' => 'Messe Berlin', 'country' => 'Germany'], ['name' => 'Fira Barcelona', 'country' => 'Spain'],
            ['name' => 'GL Events', 'country' => 'France'], ['name' => 'ViacomCBS (Events)', 'country' => 'USA'],
            ['name' => 'Disney Parks & Events', 'country' => 'USA'], ['name' => 'Universal Studios', 'country' => 'USA'],
            ['name' => 'Six Flags', 'country' => 'USA'], ['name' => 'Merlin Entertainments', 'country' => 'UK'],
            ['name' => 'Parques Reunidos', 'country' => 'Spain'], ['name' => 'Compagnie des Alpes', 'country' => 'France'],
            ['name' => 'Cirque du Soleil', 'country' => 'Canada'], ['name' => 'Feld Entertainment', 'country' => 'USA'],
            ['name' => 'MSG Entertainment', 'country' => 'USA'], ['name' => 'HYBE', 'country' => 'South Korea'],
            ['name' => 'SM Entertainment', 'country' => 'South Korea'], ['name' => 'YG Entertainment', 'country' => 'South Korea'],
            ['name' => 'JYP Entertainment', 'country' => 'South Korea'], ['name' => 'Bilibili World', 'country' => 'China'],
            ['name' => 'Comic-Con International', 'country' => 'USA'], ['name' => 'TED', 'country' => 'USA'],
            ['name' => 'SXSW', 'country' => 'USA'], ['name' => 'Web Summit', 'country' => 'Ireland'],
            ['name' => 'CES', 'country' => 'USA'], ['name' => 'Mobile World Congress', 'country' => 'Spain'],
            ['name' => 'Art Basel', 'country' => 'Switzerland'], ['name' => 'Tomorrowland', 'country' => 'Belgium'],
            ['name' => 'Coachella', 'country' => 'USA'], ['name' => 'Glastonbury Festival', 'country' => 'UK'],
            ['name' => 'Burning Man', 'country' => 'USA'], ['name' => 'Cannes Lions', 'country' => 'France'],
            ['name' => 'Sundance', 'country' => 'USA'], ['name' => 'Berlinale', 'country' => 'Germany'],
            ['name' => 'Venice Biennale', 'country' => 'Italy'], ['name' => 'Expo 2025/2030', 'country' => 'Global'],
            ['name' => 'Kassir.ru', 'country' => 'Russia'], ['name' => 'Yandex Afisha', 'country' => 'Russia'],
            ['name' => 'Afisha.ru', 'country' => 'Russia'], ['name' => 'Crocus City Hall', 'country' => 'Russia'],
            ['name' => 'Expocentre', 'country' => 'Russia'], ['name' => 'VDNH', 'country' => 'Russia'],
            ['name' => 'Hermitage Events', 'country' => 'Russia'],
            ['name' => 'Kot-Event (Partner)', 'country' => 'Russia', 'is_platform_partner' => true],
        ]);
    }
}


