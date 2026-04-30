<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Enums;

enum DisplayModel: string
{
    // Android Tablets
    case SAMSUNG_GALAXY_TAB_A8 = 'samsung_galaxy_tab_a8';
    case SAMSUNG_GALAXY_TAB_S6 = 'samsung_galaxy_tab_s6';
    case SAMSUNG_GALAXY_TAB_ACTIVE3 = 'samsung_galaxy_tab_active3';
    case LENOVO_TAB_P11 = 'lenovo_tab_p11';
    case LENOVO_TAB_M10 = 'lenovo_tab_m10';
    case HUAWEI_MATEPAD_11 = 'huawei_matepad_11';
    case XIAOMI_PAD_5 = 'xiaomi_pad_5';
    case XIAOMI_PAD_6 = 'xiaomi_pad_6';

    // Windows Tablets
    case MICROSOFT_SURFACE_GO3 = 'microsoft_surface_go3';
    case MICROSOFT_SURFACE_PRO9 = 'microsoft_surface_pro9';
    case HP_ELITE_X2 = 'hp_elite_x2';
    case DELL_LATITUDE_7320 = 'dell_latitude_7320';
    case LENOVO_THINKPAD_X12 = 'lenovo_thinkpad_x12';

    // Dedicated KDS Displays
    case STAR_KDS_TOUCH = 'star_kds_touch';
    case EPSON_KDS_DISPLAY = 'epson_kds_display';
    case LOYVERSE_KDS = 'loyverse_kds';

    // Industrial Monitors
    case ELO_TOUCH_1515L = 'elo_touch_1515l';
    case ELO_TOUCH_1517L = 'elo_touch_1517l';
    case VIEWSONIC_TD1655 = 'viewsonic_td1655';
    case IIYAMA_PROLITE = 'iiyama_prolite';

    public function platform(): string
    {
        return match ($this) {
            self::SAMSUNG_GALAXY_TAB_A8,
            self::SAMSUNG_GALAXY_TAB_S6,
            self::SAMSUNG_GALAXY_TAB_ACTIVE3,
            self::LENOVO_TAB_P11,
            self::LENOVO_TAB_M10,
            self::HUAWEI_MATEPAD_11,
            self::XIAOMI_PAD_5,
            self::XIAOMI_PAD_6 => 'android',

            self::MICROSOFT_SURFACE_GO3,
            self::MICROSOFT_SURFACE_PRO9,
            self::HP_ELITE_X2,
            self::DELL_LATITUDE_7320,
            self::LENOVO_THINKPAD_X12 => 'windows',

            self::STAR_KDS_TOUCH,
            self::EPSON_KDS_DISPLAY,
            self::LOYVERSE_KDS,
            self::ELO_TOUCH_1515L,
            self::ELO_TOUCH_1517L,
            self::VIEWSONIC_TD1655,
            self::IIYAMA_PROLITE => 'dedicated',
        };
    }

    public function brand(): string
    {
        return match ($this) {
            self::SAMSUNG_GALAXY_TAB_A8,
            self::SAMSUNG_GALAXY_TAB_S6,
            self::SAMSUNG_GALAXY_TAB_ACTIVE3 => 'Samsung',

            self::LENOVO_TAB_P11,
            self::LENOVO_TAB_M10,
            self::LENOVO_THINKPAD_X12 => 'Lenovo',

            self::HUAWEI_MATEPAD_11 => 'Huawei',

            self::XIAOMI_PAD_5,
            self::XIAOMI_PAD_6 => 'Xiaomi',

            self::MICROSOFT_SURFACE_GO3,
            self::MICROSOFT_SURFACE_PRO9 => 'Microsoft',

            self::HP_ELITE_X2 => 'HP',

            self::DELL_LATITUDE_7320 => 'Dell',

            self::STAR_KDS_TOUCH => 'Star Micronics',

            self::EPSON_KDS_DISPLAY => 'Epson',

            self::LOYVERSE_KDS => 'Loyverse',

            self::ELO_TOUCH_1515L,
            self::ELO_TOUCH_1517L => 'ELO',

            self::VIEWSONIC_TD1655 => 'ViewSonic',

            self::IIYAMA_PROLITE => 'Iiyama',
        };
    }

    public function modelName(): string
    {
        return match ($this) {
            self::SAMSUNG_GALAXY_TAB_A8 => 'Galaxy Tab A8',
            self::SAMSUNG_GALAXY_TAB_S6 => 'Galaxy Tab S6',
            self::SAMSUNG_GALAXY_TAB_ACTIVE3 => 'Galaxy Tab Active3',
            self::LENOVO_TAB_P11 => 'Tab P11',
            self::LENOVO_TAB_M10 => 'Tab M10',
            self::HUAWEI_MATEPAD_11 => 'MatePad 11',
            self::XIAOMI_PAD_5 => 'Pad 5',
            self::XIAOMI_PAD_6 => 'Pad 6',

            self::MICROSOFT_SURFACE_GO3 => 'Surface Go 3',
            self::MICROSOFT_SURFACE_PRO9 => 'Surface Pro 9',
            self::HP_ELITE_X2 => 'Elite x2',
            self::DELL_LATITUDE_7320 => 'Latitude 7320',
            self::LENOVO_THINKPAD_X12 => 'ThinkPad X12',

            self::STAR_KDS_TOUCH => 'KDS Touch',
            self::EPSON_KDS_DISPLAY => 'KDS Display',
            self::LOYVERSE_KDS => 'KDS Display',
            self::ELO_TOUCH_1515L => '1515L',
            self::ELO_TOUCH_1517L => '1517L',
            self::VIEWSONIC_TD1655 => 'TD1655',
            self::IIYAMA_PROLITE => 'ProLite',
        };
    }

    public function screenSize(): float
    {
        // Размер экрана в дюймах
        return match ($this) {
            self::SAMSUNG_GALAXY_TAB_A8 => 10.5,
            self::SAMSUNG_GALAXY_TAB_S6 => 10.4,
            self::SAMSUNG_GALAXY_TAB_ACTIVE3 => 8.0,
            self::LENOVO_TAB_P11 => 11.5,
            self::LENOVO_TAB_M10 => 10.3,
            self::HUAWEI_MATEPAD_11 => 11.0,
            self::XIAOMI_PAD_5 => 11.0,
            self::XIAOMI_PAD_6 => 11.0,

            self::MICROSOFT_SURFACE_GO3 => 10.5,
            self::MICROSOFT_SURFACE_PRO9 => 13.0,
            self::HP_ELITE_X2 => 13.5,
            self::DELL_LATITUDE_7320 => 13.0,
            self::LENOVO_THINKPAD_X12 => 12.3,

            self::STAR_KDS_TOUCH => 15.0,
            self::EPSON_KDS_DISPLAY => 15.0,
            self::LOYVERSE_KDS => 15.0,
            self::ELO_TOUCH_1515L => 15.0,
            self::ELO_TOUCH_1517L => 15.0,
            self::VIEWSONIC_TD1655 => 16.0,
            self::IIYAMA_PROLITE => 24.0,
        };
    }

    public function resolution(): string
    {
        return match ($this) {
            self::SAMSUNG_GALAXY_TAB_A8 => '1920x1200',
            self::SAMSUNG_GALAXY_TAB_S6 => '2000x1200',
            self::SAMSUNG_GALAXY_TAB_ACTIVE3 => '1920x1200',
            self::LENOVO_TAB_P11 => '2000x1200',
            self::LENOVO_TAB_M10 => '1920x1200',
            self::HUAWEI_MATEPAD_11 => '2560x1600',
            self::XIAOMI_PAD_5 => '2560x1600',
            self::XIAOMI_PAD_6 => '2880x1800',

            self::MICROSOFT_SURFACE_GO3 => '1920x1280',
            self::MICROSOFT_SURFACE_PRO9 => '2880x1920',
            self::HP_ELITE_X2 => '3000x2000',
            self::DELL_LATITUDE_7320 => '1920x1200',
            self::LENOVO_THINKPAD_X12 => '1920x1280',

            self::STAR_KDS_TOUCH => '1920x1080',
            self::EPSON_KDS_DISPLAY => '1920x1080',
            self::LOYVERSE_KDS => '1920x1080',
            self::ELO_TOUCH_1515L => '1024x768',
            self::ELO_TOUCH_1517L => '1280x1024',
            self::VIEWSONIC_TD1655 => '1920x1080',
            self::IIYAMA_PROLITE => '1920x1080',
        };
    }

    public function isTouchscreen(): bool
    {
        return match ($this) {
            self::ELO_TOUCH_1515L,
            self::ELO_TOUCH_1517L,
            self::VIEWSONIC_TD1655,
            self::IIYAMA_PROLITE => true,
            default => true, // All tablets and dedicated KDS are touchscreen
        };
    }

    public function isRugged(): bool
    {
        return match ($this) {
            self::SAMSUNG_GALAXY_TAB_ACTIVE3 => true, // Rugged tablet
            self::STAR_KDS_TOUCH => true, // Industrial KDS
            default => false,
        };
    }

    public function recommendedFor(): string
    {
        return match ($this) {
            // Rugged for harsh kitchen environments
            self::SAMSUNG_GALAXY_TAB_ACTIVE3,
            self::STAR_KDS_TOUCH,
            self::EPSON_KDS_DISPLAY => 'harsh_environment',

            // Large screens for expedition
            self::LENOVO_TAB_P11,
            self::HUAWEI_MATEPAD_11,
            self::XIAOMI_PAD_5,
            self::XIAOMI_PAD_6,
            self::MICROSOFT_SURFACE_PRO9,
            self::IIYAMA_PROLITE => 'expedition',

            // Standard kitchen stations
            self::SAMSUNG_GALAXY_TAB_A8,
            self::SAMSUNG_GALAXY_TAB_S6,
            self::LENOVO_TAB_M10,
            self::MICROSOFT_SURFACE_GO3,
            self::ELO_TOUCH_1515L,
            self::ELO_TOUCH_1517L,
            self::VIEWSONIC_TD1655 => 'standard',

            // Mobile/portable
            self::HP_ELITE_X2,
            self::DELL_LATITUDE_7320,
            self::LENOVO_THINKPAD_X12 => 'mobile',
        };
    }

    public function supportsPWA(): bool
    {
        return match ($this) {
            self::SAMSUNG_GALAXY_TAB_A8,
            self::SAMSUNG_GALAXY_TAB_S6,
            self::SAMSUNG_GALAXY_TAB_ACTIVE3,
            self::LENOVO_TAB_P11,
            self::LENOVO_TAB_M10,
            self::HUAWEI_MATEPAD_11,
            self::XIAOMI_PAD_5,
            self::XIAOMI_PAD_6 => true, // Android 11+ supports PWA well

            self::MICROSOFT_SURFACE_GO3,
            self::MICROSOFT_SURFACE_PRO9,
            self::HP_ELITE_X2,
            self::DELL_LATITUDE_7320,
            self::LENOVO_THINKPAD_X12 => true, // Windows supports PWA

            // Dedicated KDS may have custom apps
            self::STAR_KDS_TOUCH,
            self::EPSON_KDS_DISPLAY,
            self::LOYVERSE_KDS => false,

            // Industrial monitors need connected device
            self::ELO_TOUCH_1515L,
            self::ELO_TOUCH_1517L,
            self::VIEWSONIC_TD1655,
            self::IIYAMA_PROLITE => false,
        };
    }

    public function minAndroidVersion(): ?string
    {
        return match ($this) {
            self::SAMSUNG_GALAXY_TAB_A8 => '11',
            self::SAMSUNG_GALAXY_TAB_S6 => '12',
            self::SAMSUNG_GALAXY_TAB_ACTIVE3 => '11',
            self::LENOVO_TAB_P11 => '11',
            self::LENOVO_TAB_M10 => '10',
            self::HUAWEI_MATEPAD_11 => '10',
            self::XIAOMI_PAD_5 => '11',
            self::XIAOMI_PAD_6 => '13',
            default => null,
        };
    }

    public function description(): string
    {
        $platform = match ($this->platform()) {
            'android' => 'Android',
            'windows' => 'Windows',
            'dedicated' => 'Dedicated KDS',
        };

        return sprintf(
            '%s %s (%s, %.1f", %s%s)',
            $this->brand(),
            $this->modelName(),
            $platform,
            $this->screenSize(),
            $this->resolution(),
            $this->isRugged() ? ', Rugged' : ''
        );
    }
}
