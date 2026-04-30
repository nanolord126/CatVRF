<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Enums;

enum PrinterModel: string
{
    // Star Micronics
    case STAR_TSP100III = 'star_tsp100iii';
    case STAR_TSP650II = 'star_tsp650ii';
    case STAR_TSP700II = 'star_tsp700ii';
    case STAR_SM_S210I = 'star_sm_s210i';
    case STAR_SM_S220I = 'star_sm_s220i';
    case STAR_SM_T300I = 'star_sm_t300i';
    case STAR_SM_T400I = 'star_sm_t400i';

    // Epson
    case EPSON_TM_T20II = 'epson_tm_t20ii';
    case EPSON_TM_T82II = 'epson_tm_t82ii';
    case EPSON_TM_T88V = 'epson_tm_t88v';
    case EPSON_TM_T90 = 'epson_tm_t90';
    case EPSON_TM_M30 = 'epson_tm_m30';
    case EPSON_TM_L90 = 'epson_tm_l90';

    // Citizen
    case CITIZEN_CT_S310II = 'citizen_ct_s310ii';
    case CITIZEN_CT_S2000 = 'citizen_ct_s2000';
    case CITIZEN_CT_E310 = 'citizen_ct_e310';
    case CITIZEN_CT_S651II = 'citizen_ct_s651ii';

    // Bixolon
    case BIXOLON_SRP_350III = 'bixolon_srp_350iii';
    case BIXOLON_SRP_270 = 'bixolon_srp_270';
    case BIXOLON_SRP_350PLUS = 'bixolon_srp_350plus';

    // Zebra
    case ZEBRA_GK420T = 'zebra_gk420t';
    case ZEBRA_ZD420 = 'zebra_zd420';
    case ZEBRA_ZD620 = 'zebra_zd620';

    public function brand(): string
    {
        return match ($this) {
            self::STAR_TSP100III,
            self::STAR_TSP650II,
            self::STAR_TSP700II,
            self::STAR_SM_S210I,
            self::STAR_SM_S220I,
            self::STAR_SM_T300I,
            self::STAR_SM_T400I => 'Star Micronics',

            self::EPSON_TM_T20II,
            self::EPSON_TM_T82II,
            self::EPSON_TM_T88V,
            self::EPSON_TM_T90,
            self::EPSON_TM_M30,
            self::EPSON_TM_L90 => 'Epson',

            self::CITIZEN_CT_S310II,
            self::CITIZEN_CT_S2000,
            self::CITIZEN_CT_E310,
            self::CITIZEN_CT_S651II => 'Citizen',

            self::BIXOLON_SRP_350III,
            self::BIXOLON_SRP_270,
            self::BIXOLON_SRP_350PLUS => 'Bixolon',

            self::ZEBRA_GK420T,
            self::ZEBRA_ZD420,
            self::ZEBRA_ZD620 => 'Zebra',
        };
    }

    public function modelName(): string
    {
        return match ($this) {
            self::STAR_TSP100III => 'TSP100III',
            self::STAR_TSP650II => 'TSP650II',
            self::STAR_TSP700II => 'TSP700II',
            self::STAR_SM_S210I => 'SM-S210i',
            self::STAR_SM_S220I => 'SM-S220i',
            self::STAR_SM_T300I => 'SM-T300i',
            self::STAR_SM_T400I => 'SM-T400i',

            self::EPSON_TM_T20II => 'TM-T20II',
            self::EPSON_TM_T82II => 'TM-T82II',
            self::EPSON_TM_T88V => 'TM-T88V',
            self::EPSON_TM_T90 => 'TM-T90',
            self::EPSON_TM_M30 => 'TM-M30',
            self::EPSON_TM_L90 => 'TM-L90',

            self::CITIZEN_CT_S310II => 'CT-S310II',
            self::CITIZEN_CT_S2000 => 'CT-S2000',
            self::CITIZEN_CT_E310 => 'CT-E310',
            self::CITIZEN_CT_S651II => 'CT-S651II',

            self::BIXOLON_SRP_350III => 'SRP-350III',
            self::BIXOLON_SRP_270 => 'SRP-270',
            self::BIXOLON_SRP_350PLUS => 'SRP-350PLUS',

            self::ZEBRA_GK420T => 'GK420T',
            self::ZEBRA_ZD420 => 'ZD420',
            self::ZEBRA_ZD620 => 'ZD620',
        };
    }

    public function commandSet(): string
    {
        return match ($this-> {
            // Star Micronics uses StarLine commands
            self::STAR_TSP100III,
            self::STAR_TSP650II,
            self::STAR_TSP700II,
            self::STAR_SM_S210I,
            self::STAR_SM_S220I,
            self::STAR_SM_T300I,
            self::STAR_SM_T400I => 'starline',

            // Epson uses ESC/POS
            self::EPSON_TM_T20II,
            self::EPSON_TM_T82II,
            self::EPSON_TM_T88V,
            self::EPSON_TM_T90,
            self::EPSON_TM_M30,
            self::EPSON_TM_L90 => 'esc_pos',

            // Citizen uses ESC/POS compatible
            self::CITIZEN_CT_S310II,
            self::CITIZEN_CT_S2000,
            self::CITIZEN_CT_E310,
            self::CITIZEN_CT_S651II => 'esc_pos',

            // Bixolon uses ESC/POS compatible
            self::BIXOLON_SRP_350III,
            self::BIXOLON_SRP_270,
            self::BIXOLON_SRP_350PLUS => 'esc_pos',

            // Zebra uses ZPL
            self::ZEBRA_GK420T,
            self::ZEBRA_ZD420,
            self::ZEBRA_ZD620 => 'zpl',
        };
    }

    public function paperWidth(): int
    {
        // Ширина бумаги в мм
        return match ($this) {
            self::STAR_TSP100III => 80,
            self::STAR_TSP650II => 80,
            self::STAR_TSP700II => 80,
            self::STAR_SM_S210I => 58,
            self::STAR_SM_S220I => 80,
            self::STAR_SM_T300I => 80,
            self::STAR_SM_T400I => 112,

            self::EPSON_TM_T20II => 80,
            self::EPSON_TM_T82II => 80,
            self::EPSON_TM_T88V => 80,
            self::EPSON_TM_T90 => 80,
            self::EPSON_TM_M30 => 80,
            self::EPSON_TM_L90 => 80,

            self::CITIZEN_CT_S310II => 80,
            self::CITIZEN_CT_S2000 => 80,
            self::CITIZEN_CT_E310 => 58,
            self::CITIZEN_CT_S651II => 80,

            self::BIXOLON_SRP_350III => 80,
            self::BIXOLON_SRP_270 => 80,
            self::BIXOLON_SRP_350PLUS => 80,

            self::ZEBRA_GK420T => 104,
            self::ZEBRA_ZD420 => 104,
            self::ZEBRA_ZD620 => 104,
        };
    }

    public function maxDpi(): int
    {
        // Максимальное DPI
        return match ($this) {
            self::STAR_TSP100III => 203,
            self::STAR_TSP650II => 203,
            self::STAR_TSP700II => 203,
            self::STAR_SM_S210I => 203,
            self::STAR_SM_S220I => 203,
            self::STAR_SM_T300I => 203,
            self::STAR_SM_T400I => 203,

            self::EPSON_TM_T20II => 203,
            self::EPSON_TM_T82II => 203,
            self::EPSON_TM_T88V => 203,
            self::EPSON_TM_T90 => 203,
            self::EPSON_TM_M30 => 203,
            self::EPSON_TM_L90 => 203,

            self::CITIZEN_CT_S310II => 203,
            self::CITIZEN_CT_S2000 => 203,
            self::CITIZEN_CT_E310 => 203,
            self::CITIZEN_CT_S651II => 203,

            self::BIXOLON_SRP_350III => 203,
            self::BIXOLON_SRP_270 => 203,
            self::BIXOLON_SRP_350PLUS => 203,

            self::ZEBRA_GK420T => 203,
            self::ZEBRA_ZD420 => 300,
            self::ZEBRA_ZD620 => 300,
        };
    }

    public function supportsBarcode(): bool
    {
        return match ($this) {
            self::STAR_SM_S210I,
            self::STAR_SM_S220I => false, // Mobile printers without barcode
            default => true,
        };
    }

    public function supportsQR(): bool
    {
        return match ($this) {
            self::STAR_SM_S210I,
            self::STAR_SM_S220I => false,
            default => true,
        };
    }

    public function supportsGraphics(): bool
    {
        return match ($this) {
            self::STAR_SM_S210I,
            self::STAR_SM_S220I => false,
            default => true,
        };
    }

    public function recommendedFor(): string
    {
        return match ($this) {
            // Mobile printers
            self::STAR_SM_S210I,
            self::STAR_SM_S220I,
            self::EPSON_TM_M30 => 'mobile',

            // High-volume
            self::STAR_TSP700II,
            self::EPSON_TM_T88V,
            self::EPSON_TM_T90,
            self::CITIZEN_CT_S651II,
            self::BIXOLON_SRP_350PLUS => 'high_volume',

            // Standard kitchen
            self::STAR_TSP100III,
            self::STAR_TSP650II,
            self::EPSON_TM_T20II,
            self::EPSON_TM_T82II,
            self::CITIZEN_CT_S310II,
            self::CITIZEN_CT_S2000,
            self::BIXOLON_SRP_350III,
            self::BIXOLON_SRP_270 => 'standard',

            // Label printers
            self::STAR_SM_T300I,
            self::STAR_SM_T400I,
            self::EPSON_TM_L90,
            self::CITIZEN_CT_E310,
            self::ZEBRA_GK420T,
            self::ZEBRA_ZD420,
            self::ZEBRA_ZD620 => 'label',
        };
    }

    public function defaultPort(): int
    {
        return match ($this) {
            default => 9100,
        };
    }

    public function description(): string
    {
        return sprintf(
            '%s %s (%dmm, %d DPI, %s)',
            $this->brand(),
            $this->modelName(),
            $this->paperWidth(),
            $this->maxDpi(),
            $this->commandSet()
        );
    }
}
