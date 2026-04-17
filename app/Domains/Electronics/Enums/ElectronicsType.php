<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Enums;

use App\Domains\Electronics\DTOs\FilterConfigDto;

enum ElectronicsType: string
{
    // Mobile devices
    case SMARTPHONES = 'smartphones';
    case TABLETS = 'tablets';
    case E_READERS = 'e_readers';
    case MOBILE_PHONES = 'mobile_phones';

    // Computers
    case LAPTOPS = 'laptops';
    case DESKTOPS = 'desktops';
    case MONITORS = 'monitors';
    case ALL_IN_ONE = 'all_in_one';
    case MINI_PC = 'mini_pc';
    case WORKSTATIONS = 'workstations';

    // Components
    case MOTHERBOARDS = 'motherboards';
    case PROCESSORS = 'processors';
    case VIDEO_CARDS = 'video_cards';
    case RAM = 'ram';
    case STORAGE = 'storage';
    case POWER_SUPPLIES = 'power_supplies';
    case COMPUTER_CASES = 'computer_cases';
    case COOLING = 'cooling';

    // Peripherals
    case KEYBOARDS = 'keyboards';
    case MICE = 'mice';
    case HEADPHONES = 'headphones';
    case WEBCAMS = 'webcams';
    case MICROPHONES = 'microphones';
    case SPEAKERS = 'speakers';
    case PRINTERS = 'printers';
    case SCANNERS = 'scanners';
    case PROJECTORS = 'projectors';

    // TV & Video
    case TV = 'tv';
    case TV_ACCESSORIES = 'tv_accessories';
    case HOME_THEATER = 'home_theater';
    case MEDIA_PLAYERS = 'media_players';
    case STREAMING_DEVICES = 'streaming_devices';

    // Cameras & Optics
    case CAMERAS = 'cameras';
    case LENSES = 'lenses';
    case CAMERA_ACCESSORIES = 'camera_accessories';
    case BINOCULARS = 'binoculars';
    case TELESCOPES = 'telescopes';
    case DRONES = 'drones';
    case ACTION_CAMERAS = 'action_cameras';

    // Audio
    case AUDIO = 'audio';
    case HOME_AUDIO = 'home_audio';
    case CAR_AUDIO = 'car_audio';
    case PORTABLE_AUDIO = 'portable_audio';
    case HI_FI = 'hi_fi';
    case DJ_EQUIPMENT = 'dj_equipment';

    // Smart & Wearable
    case SMARTWATCHES = 'smartwatches';
    case FITNESS_TRACKERS = 'fitness_trackers';
    case SMART_RINGS = 'smart_rings';
    case SMART_GLASSES = 'smart_glasses';
    case VR_AR = 'vr_ar';
    case WEARABLE = 'wearable';

    // Gaming
    case GAMING = 'gaming';
    case GAME_CONSOLES = 'game_consoles';
    case VIDEO_GAMES = 'video_games';
    case GAMING_ACCESSORIES = 'gaming_accessories';
    case GAMING_CHAIRS = 'gaming_chairs';
    case GAMING_DESKS = 'gaming_desks';

    // Networking
    case NETWORKING = 'networking';
    case ROUTERS = 'routers';
    case SWITCHES = 'switches';
    case NETWORK_CABLES = 'network_cables';
    case WIFI_EQUIPMENT = 'wifi_equipment';
    case NETWORK_STORAGE = 'network_storage';

    // Smart Home
    case HOME_AUTOMATION = 'home_automation';
    case SMART_LIGHTING = 'smart_lighting';
    case SMART_THERMOSTATS = 'smart_thermostats';
    case SMART_LOCKS = 'smart_locks';
    case SMART_SECURITY = 'smart_security';
    case SMART_PLUGS = 'smart_plugs';
    case HOME_SENSORS = 'home_sensors';

    // Appliances
    case APPLIANCES = 'appliances';
    case KITCHEN_APPLIANCES = 'kitchen_appliances';
    case CLIMATE_CONTROL = 'climate_control';
    case VACUUM_CLEANERS = 'vacuum_cleaners';
    case IRONING = 'ironing';
    case LAUNDRY = 'laundry';
    case DISHWASHERS = 'dishwashers';

    // Car Electronics
    case CAR_ELECTRONICS = 'car_electronics';
    case CAR_AUDIO = 'car_audio_systems';
    case CAR_NAVIGATION = 'car_navigation';
    case CAR_VIDEO = 'car_video';
    case CAR_ACCESSORIES = 'car_accessories';
    case DASH_CAMS = 'dash_cams';
    case CAR_RADAR = 'car_radar';

    // Accessories
    case ACCESSORIES = 'accessories';
    case CABLES = 'cables';
    case ADAPTERS = 'adapters';
    case CHARGERS = 'chargers';
    case BATTERIES = 'batteries';
    case POWER_BANKS = 'power_banks';
    case PHONE_CASES = 'phone_cases';
    case SCREEN_PROTECTORS = 'screen_protectors';
    case STANDS = 'stands';
    case MOUNTS = 'mounts';

    // Office Equipment
    case OFFICE_EQUIPMENT = 'office_equipment';
    case SHREDDERS = 'shredders';
    case LAMINATORS = 'laminators';
    case CALCULATORS = 'calculators';
    case LABEL_MAKERS = 'label_makers';

    // Software
    case SOFTWARE = 'software';
    case OPERATING_SYSTEMS = 'operating_systems';
    case ANTIVIRUS = 'antivirus';
    case OFFICE_SOFTWARE = 'office_software';
    case CREATIVE_SOFTWARE = 'creative_software';

    // Health & Beauty Tech
    case HEALTH_TECH = 'health_tech';
    case BEAUTY_DEVICES = 'beauty_devices';
    case MASSAGE_DEVICES = 'massage_devices';
    case MEDICAL_DEVICES = 'medical_devices';

    // Hobby & DIY
    case HOBBY_ELECTRONICS = 'hobby_electronics';
    case ARDUINO = 'arduino';
    case RASPBERRY_PI = 'raspberry_pi';
    case 3D_PRINTERS = '3d_printers';
    case 3D_SCANNERS = '3d_scanners';
    case TOOLS = 'tools';
    case SOLDERING = 'soldering';

    public function getLabel(): string
    {
        return match ($this) {
            // Mobile devices
            self::SMARTPHONES => 'Смартфоны',
            self::TABLETS => 'Планшеты',
            self::E_READERS => 'Электронные книги',
            self::MOBILE_PHONES => 'Мобильные телефоны',
            // Computers
            self::LAPTOPS => 'Ноутбуки',
            self::DESKTOPS => 'Системные блоки',
            self::MONITORS => 'Мониторы',
            self::ALL_IN_ONE => 'Моноблоки',
            self::MINI_PC => 'Мини-ПК',
            self::WORKSTATIONS => 'Рабочие станции',
            // Components
            self::MOTHERBOARDS => 'Материнские платы',
            self::PROCESSORS => 'Процессоры',
            self::VIDEO_CARDS => 'Видеокарты',
            self::RAM => 'Оперативная память',
            self::STORAGE => 'Накопители',
            self::POWER_SUPPLIES => 'Блоки питания',
            self::COMPUTER_CASES => 'Корпуса',
            self::COOLING => 'Охлаждение',
            // Peripherals
            self::KEYBOARDS => 'Клавиатуры',
            self::MICE => 'Мыши',
            self::HEADPHONES => 'Наушники',
            self::WEBCAMS => 'Веб-камеры',
            self::MICROPHONES => 'Микрофоны',
            self::SPEAKERS => 'Акустические системы',
            self::PRINTERS => 'Принтеры',
            self::SCANNERS => 'Сканеры',
            self::PROJECTORS => 'Проекторы',
            // TV & Video
            self::TV => 'Телевизоры',
            self::TV_ACCESSORIES => 'Аксессуары для ТВ',
            self::HOME_THEATER => 'Домашний кинотеатр',
            self::MEDIA_PLAYERS => 'Медиаплееры',
            self::STREAMING_DEVICES => 'Стриминговые устройства',
            // Cameras & Optics
            self::CAMERAS => 'Фотоаппараты',
            self::LENSES => 'Объективы',
            self::CAMERA_ACCESSORIES => 'Аксессуары для камер',
            self::BINOCULARS => 'Бинокли',
            self::TELESCOPES => 'Телескопы',
            self::DRONES => 'Дроны',
            self::ACTION_CAMERAS => 'Экшн-камеры',
            // Audio
            self::AUDIO => 'Аудиотехника',
            self::HOME_AUDIO => 'Домашняя аудиосистема',
            self::CAR_AUDIO => 'Автоаудио',
            self::PORTABLE_AUDIO => 'Портативная аудиотехника',
            self::HI_FI => 'Hi-Fi',
            self::DJ_EQUIPMENT => 'DJ оборудование',
            // Smart & Wearable
            self::SMARTWATCHES => 'Смарт-часы',
            self::FITNESS_TRACKERS => 'Фитнес-браслеты',
            self::SMART_RINGS => 'Смарт-кольца',
            self::SMART_GLASSES => 'Смарт-очки',
            self::VR_AR => 'VR/AR очки',
            self::WEARABLE => 'Носимые устройства',
            // Gaming
            self::GAMING => 'Игры',
            self::GAME_CONSOLES => 'Игровые консоли',
            self::VIDEO_GAMES => 'Видеоигры',
            self::GAMING_ACCESSORIES => 'Игровые аксессуары',
            self::GAMING_CHAIRS => 'Игровые кресла',
            self::GAMING_DESKS => 'Игровые столы',
            // Networking
            self::NETWORKING => 'Сетевое оборудование',
            self::ROUTERS => 'Роутеры',
            self::SWITCHES => 'Коммутаторы',
            self::NETWORK_CABLES => 'Сетевые кабели',
            self::WIFI_EQUIPMENT => 'Wi-Fi оборудование',
            self::NETWORK_STORAGE => 'Сетевые хранилища',
            // Smart Home
            self::HOME_AUTOMATION => 'Умный дом',
            self::SMART_LIGHTING => 'Умное освещение',
            self::SMART_THERMOSTATS => 'Умные термостаты',
            self::SMART_LOCKS => 'Умные замки',
            self::SMART_SECURITY => 'Умная безопасность',
            self::SMART_PLUGS => 'Умные розетки',
            self::HOME_SENSORS => 'Датчики',
            // Appliances
            self::APPLIANCES => 'Бытовая техника',
            self::KITCHEN_APPLIANCES => 'Кухонная техника',
            self::CLIMATE_CONTROL => 'Климатическое оборудование',
            self::VACUUM_CLEANERS => 'Пылесосы',
            self::IRONING => 'Утюги',
            self::LAUNDRY => 'Стиральные машины',
            self::DISHWASHERS => 'Посудомоечные машины',
            // Car Electronics
            self::CAR_ELECTRONICS => 'Автоэлектроника',
            self::CAR_AUDIO => 'Автомагнитолы',
            self::CAR_NAVIGATION => 'Автонавигация',
            self::CAR_VIDEO => 'Автовидео',
            self::CAR_ACCESSORIES => 'Автоаксессуары',
            self::DASH_CAMS => 'Видеорегистраторы',
            self::CAR_RADAR => 'Радар-детекторы',
            // Accessories
            self::ACCESSORIES => 'Аксессуары',
            self::CABLES => 'Кабели',
            self::ADAPTERS => 'Адаптеры',
            self::CHARGERS => 'Зарядные устройства',
            self::BATTERIES => 'Батарейки',
            self::POWER_BANKS => 'Пауэрбанки',
            self::PHONE_CASES => 'Чехлы',
            self::SCREEN_PROTECTORS => 'Защитные стекла',
            self::STANDS => 'Подставки',
            self::MOUNTS => 'Крепления',
            // Office Equipment
            self::OFFICE_EQUIPMENT => 'Оргтехника',
            self::SHREDDERS => 'Шредеры',
            self::LAMINATORS => 'Ламинаторы',
            self::CALCULATORS => 'Калькуляторы',
            self::LABEL_MAKERS => 'Лейбл-мейкеры',
            // Software
            self::SOFTWARE => 'Программное обеспечение',
            self::OPERATING_SYSTEMS => 'Операционные системы',
            self::ANTIVIRUS => 'Антивирусы',
            self::OFFICE_SOFTWARE => 'Офисное ПО',
            self::CREATIVE_SOFTWARE => 'Творческое ПО',
            // Health & Beauty Tech
            self::HEALTH_TECH => 'Медицинская техника',
            self::BEAUTY_DEVICES => 'Бьюти-гаджеты',
            self::MASSAGE_DEVICES => 'Массажёры',
            self::MEDICAL_DEVICES => 'Медицинские приборы',
            // Hobby & DIY
            self::HOBBY_ELECTRONICS => 'Электроника для хобби',
            self::ARDUINO => 'Arduino',
            self::RASPBERRY_PI => 'Raspberry Pi',
            self::3D_PRINTERS => '3D-принтеры',
            self::3D_SCANNERS => '3D-сканеры',
            self::TOOLS => 'Инструменты',
            self::SOLDERING => 'Пайка',
            self::SMARTWATCHES => 'Смарт-часы',
            self::GAMING => 'Игровые консоли',
            self::AUDIO => 'Аудиотехника',
            self::NETWORKING => 'Сетевое оборудование',
            self::ACCESSORIES => 'Аксессуары',
            self::WEARABLE => 'Носимая электроника',
            self::HOME_AUTOMATION => 'Умный дом',
            self::CAR_ELECTRONICS => 'Автоэлектроника',
            self::APPLIANCES => 'Бытовая техника',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            // Mobile devices
            self::SMARTPHONES => 'smartphone',
            self::TABLETS => 'tablet',
            self::E_READERS => 'book',
            self::MOBILE_PHONES => 'phone',
            // Computers
            self::LAPTOPS => 'laptop',
            self::DESKTOPS => 'cpu',
            self::MONITORS => 'monitor',
            self::ALL_IN_ONE => 'monitor',
            self::MINI_PC => 'cpu',
            self::WORKSTATIONS => 'cpu',
            // Components
            self::MOTHERBOARDS => 'cpu',
            self::PROCESSORS => 'cpu',
            self::VIDEO_CARDS => 'monitor',
            self::RAM => 'memory',
            self::STORAGE => 'hard-drive',
            self::POWER_SUPPLIES => 'zap',
            self::COMPUTER_CASES => 'box',
            self::COOLING => 'snowflake',
            // Peripherals
            self::KEYBOARDS => 'keyboard',
            self::MICE => 'mouse',
            self::HEADPHONES => 'headphones',
            self::WEBCAMS => 'video',
            self::MICROPHONES => 'mic',
            self::SPEAKERS => 'volume-2',
            self::PRINTERS => 'printer',
            self::SCANNERS => 'scan',
            self::PROJECTORS => 'projector',
            // TV & Video
            self::TV => 'tv',
            self::TV_ACCESSORIES => 'tv',
            self::HOME_THEATER => 'film',
            self::MEDIA_PLAYERS => 'play',
            self::STREAMING_DEVICES => 'cast',
            // Cameras & Optics
            self::CAMERAS => 'camera',
            self::LENSES => 'aperture',
            self::CAMERA_ACCESSORIES => 'camera',
            self::BINOCULARS => 'eye',
            self::TELESCOPES => 'telescope',
            self::DRONES => 'plane',
            self::ACTION_CAMERAS => 'video',
            // Audio
            self::AUDIO => 'volume-2',
            self::HOME_AUDIO => 'speaker',
            self::CAR_AUDIO => 'volume-2',
            self::PORTABLE_AUDIO => 'headphones',
            self::HI_FI => 'speaker',
            self::DJ_EQUIPMENT => 'disc',
            // Smart & Wearable
            self::SMARTWATCHES => 'watch',
            self::FITNESS_TRACKERS => 'activity',
            self::SMART_RINGS => 'circle',
            self::SMART_GLASSES => 'eye',
            self::VR_AR => 'glasses',
            self::WEARABLE => 'watch',
            // Gaming
            self::GAMING => 'gamepad-2',
            self::GAME_CONSOLES => 'gamepad-2',
            self::VIDEO_GAMES => 'disc',
            self::GAMING_ACCESSORIES => 'gamepad-2',
            self::GAMING_CHAIRS => 'armchair',
            self::GAMING_DESKS => 'desk',
            // Networking
            self::NETWORKING => 'wifi',
            self::ROUTERS => 'wifi',
            self::SWITCHES => 'network',
            self::NETWORK_CABLES => 'cable',
            self::WIFI_EQUIPMENT => 'wifi',
            self::NETWORK_STORAGE => 'server',
            // Smart Home
            self::HOME_AUTOMATION => 'home',
            self::SMART_LIGHTING => 'lightbulb',
            self::SMART_THERMOSTATS => 'thermometer',
            self::SMART_LOCKS => 'lock',
            self::SMART_SECURITY => 'shield',
            self::SMART_PLUGS => 'plug',
            self::HOME_SENSORS => 'sensor',
            // Appliances
            self::APPLIANCES => 'blender',
            self::KITCHEN_APPLIANCES => 'blender',
            self::CLIMATE_CONTROL => 'wind',
            self::VACUUM_CLEANERS => 'wind',
            self::IRONING => 'iron',
            self::LAUNDRY => 'shirt',
            self::DISHWASHERS => 'droplet',
            // Car Electronics
            self::CAR_ELECTRONICS => 'car',
            self::CAR_AUDIO => 'volume-2',
            self::CAR_NAVIGATION => 'map',
            self::CAR_VIDEO => 'video',
            self::CAR_ACCESSORIES => 'car',
            self::DASH_CAMS => 'video',
            self::CAR_RADAR => 'radar',
            // Accessories
            self::ACCESSORIES => 'plug',
            self::CABLES => 'cable',
            self::ADAPTERS => 'plug',
            self::CHARGERS => 'zap',
            self::BATTERIES => 'battery-charging',
            self::POWER_BANKS => 'battery',
            self::PHONE_CASES => 'shield',
            self::SCREEN_PROTECTORS => 'shield',
            self::STANDS => 'stand',
            self::MOUNTS => 'mount',
            // Office Equipment
            self::OFFICE_EQUIPMENT => 'printer',
            self::SHREDDERS => 'scissors',
            self::LAMINATORS => 'layers',
            self::CALCULATORS => 'calculator',
            self::LABEL_MAKERS => 'tag',
            // Software
            self::SOFTWARE => 'package',
            self::OPERATING_SYSTEMS => 'cpu',
            self::ANTIVIRUS => 'shield',
            self::OFFICE_SOFTWARE => 'file-text',
            self::CREATIVE_SOFTWARE => 'palette',
            // Health & Beauty Tech
            self::HEALTH_TECH => 'heart',
            self::BEAUTY_DEVICES => 'sparkles',
            self::MASSAGE_DEVICES => 'hand',
            self::MEDICAL_DEVICES => 'stethoscope',
            // Hobby & DIY
            self::HOBBY_ELECTRONICS => 'cpu',
            self::ARDUINO => 'cpu',
            self::RASPBERRY_PI => 'cpu',
            self::3D_PRINTERS => 'box',
            self::3D_SCANNERS => 'scan',
            self::TOOLS => 'wrench',
            self::SOLDERING => 'flame',
        };
    }
            self::TV => 'tv',
            self::CAMERAS => 'camera',
            self::SMARTWATCHES => 'watch',
            self::GAMING => 'gamepad',
            self::AUDIO => 'speaker',
            self::NETWORKING => 'wifi',
            self::ACCESSORIES => 'plug',
            self::WEARABLE => 'watch',
            self::HOME_AUTOMATION => 'home',
            self::CAR_ELECTRONICS => 'car',
            self::APPLIANCES => 'blender',
        };
    }

    public function getFilterConfig(): FilterConfigDto
    {
        return match ($this) {
            // Mobile devices
            self::SMARTPHONES => $this->getSmartphonesFilterConfig(),
            self::TABLETS => $this->getTabletsFilterConfig(),
            self::E_READERS => $this->getGenericFilterConfig('e_readers', 'Электронные книги', 'book'),
            self::MOBILE_PHONES => $this->getGenericFilterConfig('mobile_phones', 'Мобильные телефоны', 'phone'),
            // Computers
            self::LAPTOPS => $this->getLaptopsFilterConfig(),
            self::DESKTOPS => $this->getDesktopsFilterConfig(),
            self::MONITORS => $this->getMonitorsFilterConfig(),
            self::ALL_IN_ONE => $this->getGenericFilterConfig('all_in_one', 'Моноблоки', 'monitor'),
            self::MINI_PC => $this->getGenericFilterConfig('mini_pc', 'Мини-ПК', 'cpu'),
            self::WORKSTATIONS => $this->getGenericFilterConfig('workstations', 'Рабочие станции', 'cpu'),
            // Components
            self::MOTHERBOARDS => $this->getComponentsFilterConfig('motherboards', 'Материнские платы'),
            self::PROCESSORS => $this->getComponentsFilterConfig('processors', 'Процессоры'),
            self::VIDEO_CARDS => $this->getComponentsFilterConfig('video_cards', 'Видеокарты'),
            self::RAM => $this->getComponentsFilterConfig('ram', 'Оперативная память'),
            self::STORAGE => $this->getComponentsFilterConfig('storage', 'Накопители'),
            self::POWER_SUPPLIES => $this->getComponentsFilterConfig('power_supplies', 'Блоки питания'),
            self::COMPUTER_CASES => $this->getComponentsFilterConfig('computer_cases', 'Корпуса'),
            self::COOLING => $this->getComponentsFilterConfig('cooling', 'Охлаждение'),
            // Peripherals
            self::KEYBOARDS => $this->getPeripheralsFilterConfig('keyboards', 'Клавиатуры'),
            self::MICE => $this->getPeripheralsFilterConfig('mice', 'Мыши'),
            self::HEADPHONES => $this->getHeadphonesFilterConfig(),
            self::WEBCAMS => $this->getPeripheralsFilterConfig('webcams', 'Веб-камеры'),
            self::MICROPHONES => $this->getPeripheralsFilterConfig('microphones', 'Микрофоны'),
            self::SPEAKERS => $this->getAudioFilterConfig(),
            self::PRINTERS => $this->getPeripheralsFilterConfig('printers', 'Принтеры'),
            self::SCANNERS => $this->getPeripheralsFilterConfig('scanners', 'Сканеры'),
            self::PROJECTORS => $this->getPeripheralsFilterConfig('projectors', 'Проекторы'),
            // TV & Video
            self::TV => $this->getTVFilterConfig(),
            self::TV_ACCESSORIES => $this->getGenericFilterConfig('tv_accessories', 'Аксессуары для ТВ', 'tv'),
            self::HOME_THEATER => $this->getAudioFilterConfig(),
            self::MEDIA_PLAYERS => $this->getGenericFilterConfig('media_players', 'Медиаплееры', 'play'),
            self::STREAMING_DEVICES => $this->getGenericFilterConfig('streaming_devices', 'Стриминговые устройства', 'cast'),
            // Cameras & Optics
            self::CAMERAS => $this->getCamerasFilterConfig(),
            self::LENSES => $this->getGenericFilterConfig('lenses', 'Объективы', 'aperture'),
            self::CAMERA_ACCESSORIES => $this->getGenericFilterConfig('camera_accessories', 'Аксессуары для камер', 'camera'),
            self::BINOCULARS => $this->getGenericFilterConfig('binoculars', 'Бинокли', 'eye'),
            self::TELESCOPES => $this->getGenericFilterConfig('telescopes', 'Телескопы', 'telescope'),
            self::DRONES => $this->getGenericFilterConfig('drones', 'Дроны', 'plane'),
            self::ACTION_CAMERAS => $this->getGenericFilterConfig('action_cameras', 'Экшн-камеры', 'video'),
            // Audio
            self::AUDIO => $this->getAudioFilterConfig(),
            self::HOME_AUDIO => $this->getAudioFilterConfig(),
            self::CAR_AUDIO => $this->getGenericFilterConfig('car_audio', 'Автоаудио', 'volume-2'),
            self::PORTABLE_AUDIO => $this->getHeadphonesFilterConfig(),
            self::HI_FI => $this->getAudioFilterConfig(),
            self::DJ_EQUIPMENT => $this->getGenericFilterConfig('dj_equipment', 'DJ оборудование', 'disc'),
            // Smart & Wearable
            self::SMARTWATCHES => $this->getSmartwatchesFilterConfig(),
            self::FITNESS_TRACKERS => $this->getSmartwatchesFilterConfig(),
            self::SMART_RINGS => $this->getGenericFilterConfig('smart_rings', 'Смарт-кольца', 'circle'),
            self::SMART_GLASSES => $this->getGenericFilterConfig('smart_glasses', 'Смарт-очки', 'eye'),
            self::VR_AR => $this->getGenericFilterConfig('vr_ar', 'VR/AR очки', 'glasses'),
            self::WEARABLE => $this->getSmartwatchesFilterConfig(),
            // Gaming
            self::GAMING => $this->getGamingFilterConfig(),
            self::GAME_CONSOLES => $this->getGamingFilterConfig(),
            self::VIDEO_GAMES => $this->getGenericFilterConfig('video_games', 'Видеоигры', 'disc'),
            self::GAMING_ACCESSORIES => $this->getGenericFilterConfig('gaming_accessories', 'Игровые аксессуары', 'gamepad-2'),
            self::GAMING_CHAIRS => $this->getGenericFilterConfig('gaming_chairs', 'Игровые кресла', 'armchair'),
            self::GAMING_DESKS => $this->getGenericFilterConfig('gaming_desks', 'Игровые столы', 'desk'),
            // Networking
            self::NETWORKING => $this->getNetworkingFilterConfig(),
            self::ROUTERS => $this->getNetworkingFilterConfig(),
            self::SWITCHES => $this->getNetworkingFilterConfig(),
            self::NETWORK_CABLES => $this->getGenericFilterConfig('network_cables', 'Сетевые кабели', 'cable'),
            self::WIFI_EQUIPMENT => $this->getNetworkingFilterConfig(),
            self::NETWORK_STORAGE => $this->getGenericFilterConfig('network_storage', 'Сетевые хранилища', 'server'),
            // Smart Home
            self::HOME_AUTOMATION => $this->getHomeAutomationFilterConfig(),
            self::SMART_LIGHTING => $this->getSmartHomeFilterConfig('smart_lighting', 'Умное освещение', 'lightbulb'),
            self::SMART_THERMOSTATS => $this->getSmartHomeFilterConfig('smart_thermostats', 'Умные термостаты', 'thermometer'),
            self::SMART_LOCKS => $this->getSmartHomeFilterConfig('smart_locks', 'Умные замки', 'lock'),
            self::SMART_SECURITY => $this->getSmartHomeFilterConfig('smart_security', 'Умная безопасность', 'shield'),
            self::SMART_PLUGS => $this->getSmartHomeFilterConfig('smart_plugs', 'Умные розетки', 'plug'),
            self::HOME_SENSORS => $this->getSmartHomeFilterConfig('home_sensors', 'Датчики', 'sensor'),
            // Appliances
            self::APPLIANCES => $this->getAppliancesFilterConfig(),
            self::KITCHEN_APPLIANCES => $this->getAppliancesFilterConfig(),
            self::CLIMATE_CONTROL => $this->getAppliancesFilterConfig(),
            self::VACUUM_CLEANERS => $this->getAppliancesFilterConfig(),
            self::IRONING => $this->getGenericFilterConfig('ironing', 'Утюги', 'iron'),
            self::LAUNDRY => $this->getAppliancesFilterConfig(),
            self::DISHWASHERS => $this->getAppliancesFilterConfig(),
            // Car Electronics
            self::CAR_ELECTRONICS => $this->getCarElectronicsFilterConfig(),
            self::CAR_AUDIO => $this->getGenericFilterConfig('car_audio', 'Автомагнитолы', 'volume-2'),
            self::CAR_NAVIGATION => $this->getGenericFilterConfig('car_navigation', 'Автонавигация', 'map'),
            self::CAR_VIDEO => $this->getGenericFilterConfig('car_video', 'Автовидео', 'video'),
            self::CAR_ACCESSORIES => $this->getGenericFilterConfig('car_accessories', 'Автоаксессуары', 'car'),
            self::DASH_CAMS => $this->getGenericFilterConfig('dash_cams', 'Видеорегистраторы', 'video'),
            self::CAR_RADAR => $this->getGenericFilterConfig('car_radar', 'Радар-детекторы', 'radar'),
            // Accessories
            self::ACCESSORIES => $this->getAccessoriesFilterConfig(),
            self::CABLES => $this->getGenericFilterConfig('cables', 'Кабели', 'cable'),
            self::ADAPTERS => $this->getGenericFilterConfig('adapters', 'Адаптеры', 'plug'),
            self::CHARGERS => $this->getGenericFilterConfig('chargers', 'Зарядные устройства', 'zap'),
            self::BATTERIES => $this->getGenericFilterConfig('batteries', 'Батарейки', 'battery-charging'),
            self::POWER_BANKS => $this->getGenericFilterConfig('power_banks', 'Пауэрбанки', 'battery'),
            self::PHONE_CASES => $this->getGenericFilterConfig('phone_cases', 'Чехлы', 'shield'),
            self::SCREEN_PROTECTORS => $this->getGenericFilterConfig('screen_protectors', 'Защитные стекла', 'shield'),
            self::STANDS => $this->getGenericFilterConfig('stands', 'Подставки', 'stand'),
            self::MOUNTS => $this->getGenericFilterConfig('mounts', 'Крепления', 'mount'),
            // Office Equipment
            self::OFFICE_EQUIPMENT => $this->getGenericFilterConfig('office_equipment', 'Оргтехника', 'printer'),
            self::SHREDDERS => $this->getGenericFilterConfig('shredders', 'Шредеры', 'scissors'),
            self::LAMINATORS => $this->getGenericFilterConfig('laminators', 'Ламинаторы', 'layers'),
            self::CALCULATORS => $this->getGenericFilterConfig('calculators', 'Калькуляторы', 'calculator'),
            self::LABEL_MAKERS => $this->getGenericFilterConfig('label_makers', 'Лейбл-мейкеры', 'tag'),
            // Software
            self::SOFTWARE => $this->getSoftwareFilterConfig(),
            self::OPERATING_SYSTEMS => $this->getGenericFilterConfig('operating_systems', 'Операционные системы', 'cpu'),
            self::ANTIVIRUS => $this->getGenericFilterConfig('antivirus', 'Антивирусы', 'shield'),
            self::OFFICE_SOFTWARE => $this->getGenericFilterConfig('office_software', 'Офисное ПО', 'file-text'),
            self::CREATIVE_SOFTWARE => $this->getGenericFilterConfig('creative_software', 'Творческое ПО', 'palette'),
            // Health & Beauty Tech
            self::HEALTH_TECH => $this->getGenericFilterConfig('health_tech', 'Медицинская техника', 'heart'),
            self::BEAUTY_DEVICES => $this->getBeautyDevicesFilterConfig(),
            self::MASSAGE_DEVICES => $this->getMassageDevicesFilterConfig(),
            self::MEDICAL_DEVICES => $this->getGenericFilterConfig('medical_devices', 'Медицинские приборы', 'stethoscope'),
            // Hobby & DIY
            self::HOBBY_ELECTRONICS => $this->getGenericFilterConfig('hobby_electronics', 'Электроника для хобби', 'cpu'),
            self::ARDUINO => $this->getGenericFilterConfig('arduino', 'Arduino', 'cpu'),
            self::RASPBERRY_PI => $this->getGenericFilterConfig('raspberry_pi', 'Raspberry Pi', 'cpu'),
            self::3D_PRINTERS => $this->getGenericFilterConfig('3d_printers', '3D-принтеры', 'box'),
            self::3D_SCANNERS => $this->getGenericFilterConfig('3d_scanners', '3D-сканеры', 'scan'),
            self::TOOLS => $this->getGenericFilterConfig('tools', 'Инструменты', 'wrench'),
            self::SOLDERING => $this->getGenericFilterConfig('soldering', 'Пайка', 'flame'),
        };
    }

    private function getSmartphonesFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::SMARTPHONES->value,
            label: self::SMARTPHONES->getLabel(),
            icon: self::SMARTPHONES->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Apple', 'Samsung', 'Xiaomi', 'Huawei', 'Realme', 'Vivo', 'Oppo', 'OnePlus'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'screen_size',
                    'type' => 'range',
                    'label' => 'Диагональ экрана',
                    'unit' => '"',
                    'min' => 5.5,
                    'max' => 7.2,
                    'step' => 0.1,
                ],
                [
                    'key' => 'ram',
                    'type' => 'checkbox',
                    'label' => 'Оперативная память',
                    'options' => ['4GB', '6GB', '8GB', '12GB', '16GB', '24GB'],
                ],
                [
                    'key' => 'storage',
                    'type' => 'checkbox',
                    'label' => 'Память',
                    'options' => ['64GB', '128GB', '256GB', '512GB', '1TB', '2TB'],
                ],
                [
                    'key' => 'cpu',
                    'type' => 'checkbox',
                    'label' => 'Процессор',
                    'options' => ['A16 Bionic', 'A17 Pro', 'Snapdragon 8 Gen 2', 'Snapdragon 8 Gen 3', 'Dimensity 9000', 'Dimensity 9300'],
                ],
                [
                    'key' => 'battery',
                    'type' => 'range',
                    'label' => 'Батарея',
                    'unit' => 'мАч',
                    'min' => 3000,
                    'max' => 6000,
                    'step' => 100,
                ],
                [
                    'key' => 'camera_main',
                    'type' => 'checkbox',
                    'label' => 'Основная камера',
                    'options' => ['12MP', '48MP', '50MP', '64MP', '108MP', '200MP'],
                ],
                [
                    'key' => 'camera_zoom',
                    'type' => 'checkbox',
                    'label' => 'Оптический зум',
                    'options' => ['2x', '3x', '5x', '10x'],
                ],
                [
                    'key' => 'os',
                    'type' => 'checkbox',
                    'label' => 'Операционная система',
                    'options' => ['iOS', 'Android'],
                ],
                [
                    'key' => 'network_5g',
                    'type' => 'checkbox',
                    'label' => '5G поддержка',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'nfc',
                    'type' => 'checkbox',
                    'label' => 'NFC',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'fast_charging',
                    'type' => 'checkbox',
                    'label' => 'Быстрая зарядка',
                    'options' => ['18W', '25W', '30W', '45W', '65W', '120W', '150W'],
                ],
                [
                    'key' => 'wireless_charging',
                    'type' => 'checkbox',
                    'label' => 'Беспроводная зарядка',
                    'options' => ['Да', 'Нет'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
                ['value' => 'reviews', 'label' => 'По отзывам'],
                ['value' => 'newest', 'label' => 'Сначала новинки'],
            ],
        );
    }

    private function getLaptopsFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::LAPTOPS->value,
            label: self::LAPTOPS->getLabel(),
            icon: self::LAPTOPS->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Apple', 'ASUS', 'Lenovo', 'HP', 'Dell', 'Acer', 'MSI', 'Razer', 'Huawei', 'Honor', 'Tecno', 'Infinix'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'screen_size',
                    'type' => 'checkbox',
                    'label' => 'Диагональ экрана',
                    'options' => ['до 13"', '13"-14"', '15"-16"', '17"-18"'],
                ],
                [
                    'key' => 'screen_resolution',
                    'type' => 'checkbox',
                    'label' => 'Разрешение экрана',
                    'options' => ['1366x768', '1920x1080 (FHD)', '2560x1440 (QHD)', '2560x1600', '2880x1800', '3840x2160 (4K)'],
                ],
                [
                    'key' => 'screen_type',
                    'type' => 'checkbox',
                    'label' => 'Тип матрицы',
                    'options' => ['TN', 'IPS', 'OLED', 'AMOLED', 'Retina', 'Mini-LED', 'VA'],
                ],
                [
                    'key' => 'screen_refresh_rate',
                    'type' => 'checkbox',
                    'label' => 'Частота обновления',
                    'options' => ['60Hz', '90Hz', '120Hz', '144Hz', '165Hz', '240Hz', '300Hz+'],
                ],
                [
                    'key' => 'screen_brightness',
                    'type' => 'checkbox',
                    'label' => 'Яркость',
                    'options' => ['до 250 нит', '250-300 нит', '300-400 нит', '400-500 нит', '500+ нит'],
                ],
                [
                    'key' => 'ram',
                    'type' => 'checkbox',
                    'label' => 'Оперативная память',
                    'options' => ['4GB', '8GB', '16GB', '32GB', '64GB', '128GB'],
                ],
                [
                    'key' => 'ram_type',
                    'type' => 'checkbox',
                    'label' => 'Тип памяти',
                    'options' => ['DDR4', 'DDR5', 'LPDDR4X', 'LPDDR5', 'LPDDR5X'],
                ],
                [
                    'key' => 'storage',
                    'type' => 'checkbox',
                    'label' => 'Накопитель',
                    'options' => ['128GB SSD', '256GB SSD', '512GB SSD', '1TB SSD', '2TB SSD', '4TB SSD'],
                ],
                [
                    'key' => 'storage_type',
                    'type' => 'checkbox',
                    'label' => 'Тип накопителя',
                    'options' => ['SSD', 'SSD + HDD', 'eMMC'],
                ],
                [
                    'key' => 'cpu',
                    'type' => 'checkbox',
                    'label' => 'Процессор',
                    'options' => [
                        'Intel Core i3', 'Intel Core i5', 'Intel Core i7', 'Intel Core i9',
                        'AMD Ryzen 3', 'AMD Ryzen 5', 'AMD Ryzen 7', 'AMD Ryzen 9',
                        'Apple M2', 'Apple M3', 'Apple M3 Pro', 'Apple M3 Max',
                        'Intel Core Ultra', 'AMD Ryzen AI',
                    ],
                ],
                [
                    'key' => 'cpu_cores',
                    'type' => 'checkbox',
                    'label' => 'Количество ядер',
                    'options' => ['4 ядра', '6 ядер', '8 ядер', '10 ядер', '12+ ядер'],
                ],
                [
                    'key' => 'gpu',
                    'type' => 'checkbox',
                    'label' => 'Видеокарта',
                    'options' => [
                        'Интегрированная Intel', 'Интегрированная AMD', 'Интегрированная Apple',
                        'RTX 3050', 'RTX 4050', 'RTX 4060', 'RTX 4070', 'RTX 4080', 'RTX 4090',
                        'RX 7600', 'RX 7700', 'RX 7800', 'RX 7900',
                    ],
                ],
                [
                    'key' => 'gpu_memory',
                    'type' => 'checkbox',
                    'label' => 'Память видеокарты',
                    'options' => ['Интегрированная', '4GB', '6GB', '8GB', '12GB', '16GB', '24GB'],
                ],
                [
                    'key' => 'os',
                    'type' => 'checkbox',
                    'label' => 'Операционная система',
                    'options' => ['Windows 11', 'Windows 10', 'macOS', 'Без ОС', 'Linux', 'Chrome OS'],
                ],
                [
                    'key' => 'weight',
                    'type' => 'checkbox',
                    'label' => 'Вес',
                    'options' => ['до 1.5кг', '1.5-2кг', '2-2.5кг', '2.5-3кг', '3кг+'],
                ],
                [
                    'key' => 'thickness',
                    'type' => 'checkbox',
                    'label' => 'Толщина',
                    'options' => ['до 15мм', '15-20мм', '20-25мм', '25мм+'],
                ],
                [
                    'key' => 'battery_capacity',
                    'type' => 'checkbox',
                    'label' => 'Емкость батареи',
                    'options' => ['до 50 Вт·ч', '50-70 Вт·ч', '70-90 Вт·ч', '90+ Вт·ч'],
                ],
                [
                    'key' => 'purpose',
                    'type' => 'checkbox',
                    'label' => 'Назначение',
                    'options' => ['Для работы', 'Для учёбы', 'Для игр', 'Для дизайна', 'Ультрабук', 'Геймерский', 'Для бизнеса'],
                ],
                [
                    'key' => 'touchscreen',
                    'type' => 'checkbox',
                    'label' => 'Сенсорный экран',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'webcam',
                    'type' => 'checkbox',
                    'label' => 'Веб-камера',
                    'options' => ['Нет', '720p', '1080p', '2K', '4K'],
                ],
                [
                    'key' => 'keyboard_backlight',
                    'type' => 'checkbox',
                    'label' => 'Подсветка клавиатуры',
                    'options' => ['Нет', 'Одноцветная', 'RGB'],
                ],
                [
                    'key' => 'numeric_keyboard',
                    'type' => 'checkbox',
                    'label' => 'Цифровая клавиатура',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'fingerprint',
                    'type' => 'checkbox',
                    'label' => 'Сканер отпечатка',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'color',
                    'type' => 'checkbox',
                    'label' => 'Цвет',
                    'options' => ['Черный', 'Серый', 'Серебристый', 'Белый', 'Синий', 'Розовый', 'Золотой'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
                ['value' => 'reviews', 'label' => 'По отзывам'],
                ['value' => 'newest', 'label' => 'Сначала новинки'],
                ['value' => 'performance', 'label' => 'По производительности'],
            ],
        );
    }

    private function getTabletsFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::TABLETS->value,
            label: self::TABLETS->getLabel(),
            icon: self::TABLETS->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Apple', 'Samsung', 'Xiaomi', 'Huawei', 'Lenovo', 'Asus', 'Microsoft'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'screen_size',
                    'type' => 'checkbox',
                    'label' => 'Диагональ экрана',
                    'options' => ['8"', '9"', '10"', '11"', '12"', '13"'],
                ],
                [
                    'key' => 'ram',
                    'type' => 'checkbox',
                    'label' => 'Оперативная память',
                    'options' => ['3GB', '4GB', '6GB', '8GB', '12GB', '16GB'],
                ],
                [
                    'key' => 'storage',
                    'type' => 'checkbox',
                    'label' => 'Память',
                    'options' => ['32GB', '64GB', '128GB', '256GB', '512GB', '1TB'],
                ],
                [
                    'key' => 'os',
                    'type' => 'checkbox',
                    'label' => 'Операционная система',
                    'options' => ['iPadOS', 'Android', 'Windows'],
                ],
                [
                    'key' => 'cellular',
                    'type' => 'checkbox',
                    'label' => 'Мобильная связь',
                    'options' => ['Wi-Fi', 'Wi-Fi + Cellular', '5G'],
                ],
                [
                    'key' => 'stylus',
                    'type' => 'checkbox',
                    'label' => 'Стилус',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'keyboard',
                    'type' => 'checkbox',
                    'label' => 'Клавиатура',
                    'options' => ['Да', 'Нет'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
                ['value' => 'newest', 'label' => 'Сначала новинки'],
            ],
        );
    }

    private function getHeadphonesFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::HEADPHONES->value,
            label: self::HEADPHONES->getLabel(),
            icon: self::HEADPHONES->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Apple', 'Sony', 'Bose', 'Sennheiser', 'JBL', 'Samsung', 'Xiaomi', 'Marshall', 'Audio-Technica', 'AKG', 'Beyerdynamic', 'Focal'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'type',
                    'type' => 'checkbox',
                    'label' => 'Тип',
                    'options' => ['Полноразмерные', 'Наушники-вкладыши', 'Вакуумные', 'TWS', 'Портативные', 'Студийные', 'Игровые'],
                ],
                [
                    'key' => 'connection',
                    'type' => 'checkbox',
                    'label' => 'Подключение',
                    'options' => ['Беспроводные (Bluetooth)', 'Проводные', 'Проводные + беспроводные'],
                ],
                [
                    'key' => 'bluetooth_version',
                    'type' => 'checkbox',
                    'label' => 'Версия Bluetooth',
                    'options' => ['Bluetooth 4.0', 'Bluetooth 4.2', 'Bluetooth 5.0', 'Bluetooth 5.1', 'Bluetooth 5.2', 'Bluetooth 5.3'],
                ],
                [
                    'key' => 'bluetooth_codecs',
                    'type' => 'checkbox',
                    'label' => 'Bluetooth кодеки',
                    'options' => ['SBC', 'AAC', 'aptX', 'aptX HD', 'aptX Adaptive', 'LDAC', 'LHDC'],
                ],
                [
                    'key' => 'noise_cancellation',
                    'type' => 'checkbox',
                    'label' => 'Шумоподавление',
                    'options' => ['Нет', 'Пассивное', 'Активное (ANC)', 'Гибридное ANC', 'Transparency Mode'],
                ],
                [
                    'key' => 'battery_life',
                    'type' => 'checkbox',
                    'label' => 'Автономность',
                    'options' => ['до 10ч', '10-20ч', '20-30ч', '30-40ч', '40-60ч', '60ч+'],
                ],
                [
                    'key' => 'battery_case',
                    'type' => 'checkbox',
                    'label' => 'Автономность с кейсом',
                    'options' => ['до 20ч', '20-30ч', '30-40ч', '40-50ч', '50ч+'],
                ],
                [
                    'key' => 'charging_type',
                    'type' => 'checkbox',
                    'label' => 'Тип зарядки',
                    'options' => ['Micro USB', 'USB-C', 'Lightning', 'Беспроводная (Qi)', 'Магнитная'],
                ],
                [
                    'key' => 'fast_charging',
                    'type' => 'checkbox',
                    'label' => 'Быстрая зарядка',
                    'options' => ['Нет', '5 мин = 1ч', '10 мин = 2ч', '15 мин = 3ч', 'Быстрее'],
                ],
                [
                    'key' => 'microphone',
                    'type' => 'checkbox',
                    'label' => 'Микрофон',
                    'options' => ['Нет', 'Встроенный', 'С выносным микрофоном', 'Beamforming'],
                ],
                [
                    'key' => 'microphone_type',
                    'type' => 'checkbox',
                    'label' => 'Тип микрофона',
                    'options' => ['Омни-направленный', 'Кардиоидный', 'Несколько микрофонов'],
                ],
                [
                    'key' => 'water_resistance',
                    'type' => 'checkbox',
                    'label' => 'Влагозащита',
                    'options' => ['Нет', 'IPX4', 'IPX5', 'IPX7', 'IP68'],
                ],
                [
                    'key' => 'driver_size',
                    'type' => 'checkbox',
                    'label' => 'Размер динамика',
                    'options' => ['до 30мм', '30-40мм', '40-50мм', '50-70мм', '70мм+'],
                ],
                [
                    'key' => 'frequency_range',
                    'type' => 'checkbox',
                    'label' => 'Частотный диапазон',
                    'options' => ['20Hz-20kHz', '4Hz-40kHz', '4Hz-100kHz'],
                ],
                [
                    'key' => 'impedance',
                    'type' => 'checkbox',
                    'label' => 'Импеданс',
                    'options' => ['16 Ом', '32 Ом', '64 Ом', '150 Ом', '250 Ом', '600 Ом'],
                ],
                [
                    'key' => 'sensitivity',
                    'type' => 'checkbox',
                    'label' => 'Чувствительность',
                    'options' => ['до 95 дБ', '95-100 дБ', '100-105 дБ', '105+ дБ'],
                ],
                [
                    'key' => 'wire_type',
                    'type' => 'checkbox',
                    'label' => 'Тип провода',
                    'options' => ['Обычный', 'Офисный (spiral)', 'Съемный', 'Беспроводной'],
                ],
                [
                    'key' => 'cable_length',
                    'type' => 'checkbox',
                    'label' => 'Длина провода',
                    'options' => ['до 1м', '1-1.5м', '1.5-2м', '2-3м', '3м+'],
                ],
                [
                    'key' => 'connector_type',
                    'type' => 'checkbox',
                    'label' => 'Тип разъёма',
                    'options' => ['3.5мм mini-jack', '2.5мм', '6.35мм', 'USB-C', 'Lightning', 'Bluetooth'],
                ],
                [
                    'key' => 'weight',
                    'type' => 'checkbox',
                    'label' => 'Вес',
                    'options' => ['до 200г', '200-250г', '250-300г', '300-350г', '350г+'],
                ],
                [
                    'key' => 'foldable',
                    'type' => 'checkbox',
                    'label' => 'Складная конструкция',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'controls',
                    'type' => 'checkbox',
                    'label' => 'Управление',
                    'options' => ['На наушниках', 'На кейсе', 'Сенсорное', 'Голосовое', 'Приложение'],
                ],
                [
                    'key' => 'voice_assistant',
                    'type' => 'checkbox',
                    'label' => 'Голосовой помощник',
                    'options' => ['Siri', 'Google Assistant', 'Alexa', 'Cortana', 'Bixby', 'Нет'],
                ],
                [
                    'key' => 'multipoint',
                    'type' => 'checkbox',
                    'label' => 'Multipoint (подключение к 2 устройствам)',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'spatial_audio',
                    'type' => 'checkbox',
                    'label' => 'Пространственное аудио',
                    'options' => ['Нет', '360° Audio', 'Dolby Atmos', 'Head Tracking'],
                ],
                [
                    'key' => 'equalizer',
                    'type' => 'checkbox',
                    'label' => 'Эквалайзер',
                    'options' => ['Нет', 'Встроенные пресеты', 'Настраиваемый в приложении'],
                ],
                [
                    'key' => 'color',
                    'type' => 'checkbox',
                    'label' => 'Цвет',
                    'options' => ['Черный', 'Белый', 'Серый', 'Серебристый', 'Розовый', 'Синий', 'Золотой', 'Красный', 'Зеленый'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
                ['value' => 'battery', 'label' => 'По автономности'],
                ['value' => 'popular', 'label' => 'По популярности'],
            ],
        );
    }

    private function getTVFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::TV->value,
            label: self::TV->getLabel(),
            icon: self::TV->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Samsung', 'LG', 'Sony', 'Philips', 'Xiaomi', 'TCL', 'Hisense', 'Haier', 'Hyundai', 'BBK', 'DEXP'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'screen_size',
                    'type' => 'checkbox',
                    'label' => 'Диагональ экрана',
                    'options' => ['32"', '40"', '43"', '50"', '55"', '65"', '75"', '85"', '98"', '100"+'],
                ],
                [
                    'key' => 'resolution',
                    'type' => 'checkbox',
                    'label' => 'Разрешение',
                    'options' => ['HD (1366x768)', 'Full HD (1920x1080)', '4K UHD (3840x2160)', '8K UHD (7680x4320)'],
                ],
                [
                    'key' => 'panel_type',
                    'type' => 'checkbox',
                    'label' => 'Тип матрицы',
                    'options' => ['LED', 'QLED', 'OLED', 'Mini-LED', 'MicroLED', 'LCD', 'Plasma'],
                ],
                [
                    'key' => 'refresh_rate',
                    'type' => 'checkbox',
                    'label' => 'Частота обновления',
                    'options' => ['50Hz', '60Hz', '100Hz', '120Hz', '144Hz', '240Hz'],
                ],
                [
                    'key' => 'screen_brightness',
                    'type' => 'checkbox',
                    'label' => 'Яркость',
                    'options' => ['до 300 нит', '300-400 нит', '400-500 нит', '500-700 нит', '700-1000 нит', '1000+ нит'],
                ],
                [
                    'key' => 'contrast_ratio',
                    'type' => 'checkbox',
                    'label' => 'Контрастность',
                    'options' => ['5000:1', '10000:1', '1000000:1', 'Бесконечная (OLED)'],
                ],
                [
                    'key' => 'smart_tv',
                    'type' => 'checkbox',
                    'label' => 'Smart TV',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'smart_platform',
                    'type' => 'checkbox',
                    'label' => 'Платформа Smart TV',
                    'options' => ['Tizen', 'webOS', 'Android TV', 'Google TV', 'Fire TV', 'VIDAA', 'KAI'],
                ],
                [
                    'key' => 'hdr',
                    'type' => 'checkbox',
                    'label' => 'HDR',
                    'options' => ['HDR10', 'HDR10+', 'HLG', 'Dolby Vision', 'Dolby Vision IQ', 'Hybrid Log Gamma'],
                ],
                [
                    'key' => 'dolby_atmos',
                    'type' => 'checkbox',
                    'label' => 'Dolby Atmos',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'sound_power',
                    'type' => 'checkbox',
                    'label' => 'Мощность звука',
                    'options' => ['до 10Вт', '10-20Вт', '20-30Вт', '30-50Вт', '50Вт+'],
                ],
                [
                    'key' => 'sound_system',
                    'type' => 'checkbox',
                    'label' => 'Аудиосистема',
                    'options' => ['2.0', '2.1', '4.0', '5.1', '7.1', 'Dolby Atmos'],
                ],
                [
                    'key' => 'wifi',
                    'type' => 'checkbox',
                    'label' => 'Wi-Fi',
                    'options' => ['Нет', 'Wi-Fi 4', 'Wi-Fi 5', 'Wi-Fi 6', 'Wi-Fi 6E'],
                ],
                [
                    'key' => 'bluetooth',
                    'type' => 'checkbox',
                    'label' => 'Bluetooth',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'hdmi_ports',
                    'type' => 'checkbox',
                    'label' => 'Порты HDMI',
                    'options' => ['1', '2', '3', '4'],
                ],
                [
                    'key' => 'hdmi_version',
                    'type' => 'checkbox',
                    'label' => 'Версия HDMI',
                    'options' => ['HDMI 1.4', 'HDMI 2.0', 'HDMI 2.1', 'eARC'],
                ],
                [
                    'key' => 'usb_ports',
                    'type' => 'checkbox',
                    'label' => 'Порты USB',
                    'options' => ['Нет', '1', '2', '3'],
                ],
                [
                    'key' => 'usb_version',
                    'type' => 'checkbox',
                    'label' => 'Версия USB',
                    'options' => ['USB 2.0', 'USB 3.0'],
                ],
                [
                    'key' => 'ci_plus',
                    'type' => 'checkbox',
                    'label' => 'CI+ слот',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'vga',
                    'type' => 'checkbox',
                    'label' => 'VGA вход',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'component',
                    'type' => 'checkbox',
                    'label' => 'Component вход',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'composite',
                    'type' => 'checkbox',
                    'label' => 'Composite вход',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'optical',
                    'type' => 'checkbox',
                    'label' => 'Оптический выход',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'headphone_out',
                    'type' => 'checkbox',
                    'label' => 'Выход на наушники',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'arc',
                    'type' => 'checkbox',
                    'label' => 'ARC / eARC',
                    'options' => ['Нет', 'ARC', 'eARC'],
                ],
                [
                    'key' => 'ethernet',
                    'type' => 'checkbox',
                    'label' => 'LAN порт',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'wall_mount',
                    'type' => 'checkbox',
                    'label' => 'Крепление VESA',
                    'options' => ['Нет', 'VESA 200x200', 'VESA 300x200', 'VESA 400x400', 'VESA 600x400'],
                ],
                [
                    'key' => '3d',
                    'type' => 'checkbox',
                    'label' => '3D',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'curved',
                    'type' => 'checkbox',
                    'label' => 'Изогнутый экран',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'thickness',
                    'type' => 'checkbox',
                    'label' => 'Толщина',
                    'options' => ['до 50мм', '50-70мм', '70-100мм', '100мм+'],
                ],
                [
                    'key' => 'weight',
                    'type' => 'checkbox',
                    'label' => 'Вес',
                    'options' => ['до 10кг', '10-15кг', '15-20кг', '20-25кг', '25кг+'],
                ],
                [
                    'key' => 'color',
                    'type' => 'checkbox',
                    'label' => 'Цвет',
                    'options' => ['Черный', 'Серый', 'Белый', 'Серебристый', 'Тёмно-серый'],
                ],
                [
                    'key' => 'installation_type',
                    'type' => 'checkbox',
                    'label' => 'Тип установки',
                    'options' => ['На подставке', 'На стене', 'Оба варианта'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
                ['value' => 'screen_size', 'label' => 'По размеру экрана'],
                ['value' => 'newest', 'label' => 'Сначала новинки'],
            ],
        );
    }

    private function getCamerasFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::CAMERAS->value,
            label: self::CAMERAS->getLabel(),
            icon: self::CAMERAS->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Canon', 'Nikon', 'Sony', 'Fujifilm', 'Panasonic', 'Olympus', 'Leica'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'type',
                    'type' => 'checkbox',
                    'label' => 'Тип камеры',
                    'options' => ['Зеркальная', 'Беззеркальная', 'Компактная', 'Экшн-камера', 'Среднеформатная'],
                ],
                [
                    'key' => 'sensor_size',
                    'type' => 'checkbox',
                    'label' => 'Размер сенсора',
                    'options' => ['1/2.3"', '1"', 'APS-C', 'Full Frame', 'Medium Format'],
                ],
                [
                    'key' => 'megapixels',
                    'type' => 'checkbox',
                    'label' => 'Мегапиксели',
                    'options' => ['20MP', '24MP', '30MP', '45MP', '50MP', '61MP'],
                ],
                [
                    'key' => 'video_resolution',
                    'type' => 'checkbox',
                    'label' => 'Видео',
                    'options' => ['4K 30fps', '4K 60fps', '6K 30fps', '8K 30fps'],
                ],
                [
                    'key' => 'stabilization',
                    'type' => 'checkbox',
                    'label' => 'Стабилизация',
                    'options' => ['Оптическая', 'Электронная', 'Встроенная', 'В корпусе'],
                ],
                [
                    'key' => 'weather_sealing',
                    'type' => 'checkbox',
                    'label' => 'Защита от погоды',
                    'options' => ['Да', 'Нет'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
                ['value' => 'megapixels', 'label' => 'По мегапикселям'],
            ],
        );
    }

    private function getSmartwatchesFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::SMARTWATCHES->value,
            label: self::SMARTWATCHES->getLabel(),
            icon: self::SMARTWATCHES->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Apple', 'Samsung', 'Garmin', 'Xiaomi', 'Huawei', 'Amazfit', 'Polar'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'screen_size',
                    'type' => 'checkbox',
                    'label' => 'Размер экрана',
                    'options' => ['до 1.2"', '1.2"-1.4"', '1.4"-1.6"', '1.6"-1.8"', '1.8"-2.0"', '2.0"+'],
                ],
                [
                    'key' => 'screen_type',
                    'type' => 'checkbox',
                    'label' => 'Тип экрана',
                    'options' => ['LCD', 'OLED', 'AMOLED', 'Super AMOLED', 'Retina', 'LTPO'],
                ],
                [
                    'key' => 'screen_resolution',
                    'type' => 'checkbox',
                    'label' => 'Разрешение экрана',
                    'options' => ['320x320', '368x448', '390x444', '454x454', '466x466', '480x480', '496x496'],
                ],
                [
                    'key' => 'always_on_display',
                    'type' => 'checkbox',
                    'label' => 'Always On Display',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'battery_life',
                    'type' => 'checkbox',
                    'label' => 'Автономность',
                    'options' => ['до 1 дня', '1-3 дня', '3-7 дней', '7-14 дней', '14-30 дней', '30+ дней'],
                ],
                [
                    'key' => 'charging_time',
                    'type' => 'checkbox',
                    'label' => 'Время зарядки',
                    'options' => ['до 1ч', '1-2ч', '2-3ч', '3ч+'],
                ],
                [
                    'key' => 'wireless_charging',
                    'type' => 'checkbox',
                    'label' => 'Беспроводная зарядка',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'fast_charging',
                    'type' => 'checkbox',
                    'label' => 'Быстрая зарядка',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'water_resistance',
                    'type' => 'checkbox',
                    'label' => 'Водозащита',
                    'options' => ['IP67', 'IP68', '5ATM', '10ATM', '20ATM', '50м', '100м'],
                ],
                [
                    'key' => 'compatibility',
                    'type' => 'checkbox',
                    'label' => 'Совместимость',
                    'options' => ['iOS', 'Android', 'Универсальные'],
                ],
                [
                    'key' => 'gps',
                    'type' => 'checkbox',
                    'label' => 'GPS',
                    'options' => ['Нет', 'GPS', 'GPS + GLONASS', 'GPS + GLONASS + Galileo'],
                ],
                [
                    'key' => 'nfc',
                    'type' => 'checkbox',
                    'label' => 'NFC (бесконтактная оплата)',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'ecg',
                    'type' => 'checkbox',
                    'label' => 'ЭКГ',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'heart_rate_monitor',
                    'type' => 'checkbox',
                    'label' => 'Мониторинг ЧСС',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'blood_oxygen',
                    'type' => 'checkbox',
                    'label' => 'SpO2 (насыщение кислородом)',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'blood_pressure',
                    'type' => 'checkbox',
                    'label' => 'Измерение давления',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'stress_monitor',
                    'type' => 'checkbox',
                    'label' => 'Мониторинг стресса',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'sleep_tracking',
                    'type' => 'checkbox',
                    'label' => 'Отслеживание сна',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'activity_tracking',
                    'type' => 'checkbox',
                    'label' => 'Отслеживание активности',
                    'options' => ['Шаги', 'Калории', 'Расстояние', 'Этажи', 'Все'],
                ],
                [
                    'key' => 'sports_modes',
                    'type' => 'checkbox',
                    'label' => 'Спортивные режимы',
                    'options' => ['до 10', '10-50', '50-100', '100+'],
                ],
                [
                    'key' => 'swim_tracking',
                    'type' => 'checkbox',
                    'label' => 'Отслеживание плавания',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'altimeter',
                    'type' => 'checkbox',
                    'label' => 'Альтиметр',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'compass',
                    'type' => 'checkbox',
                    'label' => 'Компас',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'barometer',
                    'type' => 'checkbox',
                    'label' => 'Барометр',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'thermometer',
                    'type' => 'checkbox',
                    'label' => 'Термометр',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'microphone',
                    'type' => 'checkbox',
                    'label' => 'Микрофон',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'speaker',
                    'type' => 'checkbox',
                    'label' => 'Динамик',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'voice_assistant',
                    'type' => 'checkbox',
                    'label' => 'Голосовой помощник',
                    'options' => ['Siri', 'Google Assistant', 'Alexa', 'Нет'],
                ],
                [
                    'key' => 'cellular',
                    'type' => 'checkbox',
                    'label' => 'Встроенная eSIM',
                    'options' => ['Нет', 'eSIM', 'eSIM + nanoSIM'],
                ],
                [
                    'key' => 'storage',
                    'type' => 'checkbox',
                    'label' => 'Встроенная память',
                    'options' => ['до 8GB', '8GB', '16GB', '32GB', '64GB'],
                ],
                [
                    'key' => 'ram',
                    'type' => 'checkbox',
                    'label' => 'Оперативная память',
                    'options' => ['до 512MB', '512MB-1GB', '1GB-2GB', '2GB+'],
                ],
                [
                    'key' => 'strap_material',
                    'type' => 'checkbox',
                    'label' => 'Материал ремешка',
                    'options' => ['Силикон', 'Кожа', 'Металл', 'Нейлон', 'Ткань', 'Керамика'],
                ],
                [
                    'key' => 'strap_width',
                    'type' => 'checkbox',
                    'label' => 'Ширина ремешка',
                    'options' => ['18мм', '20мм', '22мм', '24мм', '26мм'],
                ],
                [
                    'key' => 'weight',
                    'type' => 'checkbox',
                    'label' => 'Вес',
                    'options' => ['до 30г', '30-40г', '40-50г', '50-60г', '60г+'],
                ],
                [
                    'key' => 'case_material',
                    'type' => 'checkbox',
                    'label' => 'Материал корпуса',
                    'options' => ['Пластик', 'Алюминий', 'Сталь', 'Титан', 'Керамика'],
                ],
                [
                    'key' => 'color',
                    'type' => 'checkbox',
                    'label' => 'Цвет',
                    'options' => ['Черный', 'Белый', 'Серый', 'Серебристый', 'Розовый', 'Золотой', 'Синий', 'Зеленый', 'Красный'],
                ],
                [
                    'key' => 'blood_oxygen',
                    'type' => 'checkbox',
                    'label' => 'Измерение кислорода',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'compatibility',
                    'type' => 'checkbox',
                    'label' => 'Совместимость',
                    'options' => ['iOS', 'Android', 'Все'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
                ['value' => 'battery', 'label' => 'По автономности'],
            ],
        );
    }

    private function getGamingFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::GAMING->value,
            label: self::GAMING->getLabel(),
            icon: self::GAMING->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Sony', 'Microsoft', 'Nintendo', 'Valve', 'ASUS', 'Lenovo', 'Acer', 'Razer', 'MSI'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'platform',
                    'type' => 'checkbox',
                    'label' => 'Платформа',
                    'options' => ['PlayStation 5', 'PlayStation 5 Pro', 'Xbox Series X', 'Xbox Series S', 'Nintendo Switch', 'Nintendo Switch OLED', 'Steam Deck', 'Steam Deck OLED', 'Игровой ноутбук', 'Игровой ПК'],
                ],
                [
                    'key' => 'generation',
                    'type' => 'checkbox',
                    'label' => 'Поколение',
                    'options' => ['Текущее (9-е)', 'Предыдущее (8-е)', 'Ретро'],
                ],
                [
                    'key' => 'edition',
                    'type' => 'checkbox',
                    'label' => 'Комплектация',
                    'options' => ['Базовая', 'Digital Edition', 'Standard', 'Deluxe', 'Pro', 'Lite', 'OLED', 'All-Digital'],
                ],
                [
                    'key' => 'storage',
                    'type' => 'checkbox',
                    'label' => 'Встроенная память',
                    'options' => ['64GB', '128GB', '256GB', '512GB', '825GB', '1TB', '2TB'],
                ],
                [
                    'key' => 'storage_expandable',
                    'type' => 'checkbox',
                    'label' => 'Расширение памяти',
                    'options' => ['Нет', 'SD/microSD', 'NVMe SSD', 'M.2 SSD'],
                ],
                [
                    'key' => 'gpu',
                    'type' => 'checkbox',
                    'label' => 'Видеокарта',
                    'options' => ['AMD RDNA 2', 'AMD RDNA 3', 'Custom AMD APU', 'NVIDIA RTX 4050', 'RTX 4060', 'RTX 4070', 'RTX 4080', 'RTX 4090', 'RX 7600', 'RX 7700', 'RX 7800', 'RX 7900'],
                ],
                [
                    'key' => 'gpu_memory',
                    'type' => 'checkbox',
                    'label' => 'Память видеокарты',
                    'options' => ['8GB', '10GB', '12GB', '16GB', '24GB'],
                ],
                [
                    'key' => 'cpu',
                    'type' => 'checkbox',
                    'label' => 'Процессор',
                    'options' => ['AMD Zen 2', 'AMD Zen 3', 'AMD Zen 4', 'Custom AMD', 'Intel Core i5', 'Intel Core i7', 'Intel Core i9', 'Intel Core Ultra', 'AMD Ryzen 5', 'AMD Ryzen 7', 'AMD Ryzen 9'],
                ],
                [
                    'key' => 'ram',
                    'type' => 'checkbox',
                    'label' => 'Оперативная память',
                    'options' => ['8GB', '16GB', '32GB', '64GB'],
                ],
                [
                    'key' => 'ram_type',
                    'type' => 'checkbox',
                    'label' => 'Тип памяти',
                    'options' => ['DDR4', 'DDR5', 'LPDDR4X', 'LPDDR5', 'LPDDR5X', 'GDDR6', 'GDDR6X'],
                ],
                [
                    'key' => 'resolution',
                    'type' => 'checkbox',
                    'label' => 'Разрешение',
                    'options' => ['720p', '1080p', '1440p', '4K', '8K'],
                ],
                [
                    'key' => 'fps',
                    'type' => 'checkbox',
                    'label' => 'Кадровая частота',
                    'options' => ['30fps', '60fps', '120fps', '144fps', '240fps'],
                ],
                [
                    'key' => 'hdr',
                    'type' => 'checkbox',
                    'label' => 'HDR',
                    'options' => ['Нет', 'HDR10', 'HDR10+', 'Dolby Vision', 'HLG'],
                ],
                [
                    'key' => 'ray_tracing',
                    'type' => 'checkbox',
                    'label' => 'Ray Tracing',
                    'options' => ['Нет', 'RT', 'RTX', 'Hardware RT'],
                ],
                [
                    'key' => 'dlss_fsr',
                    'type' => 'checkbox',
                    'label' => 'Апскейлинг',
                    'options' => ['Нет', 'DLSS', 'DLSS 2', 'DLSS 3', 'FSR', 'FSR 2', 'FSR 3', 'XeSS'],
                ],
                [
                    'key' => 'controller_type',
                    'type' => 'checkbox',
                    'label' => 'Тип геймпада',
                    'options' => ['DualSense', 'DualShock 4', 'Xbox Wireless', 'Xbox Elite', 'Joy-Con', 'Pro Controller', 'Steam Controller'],
                ],
                [
                    'key' => 'controller_included',
                    'type' => 'checkbox',
                    'label' => 'Геймпад в комплекте',
                    'options' => ['Нет', '1 шт', '2 шт', '2+ шт'],
                ],
                [
                    'key' => 'controller_features',
                    'type' => 'checkbox',
                    'label' => 'Функции геймпада',
                    'options' => ['Вибрация', 'Haptic Feedback', 'Adaptive Triggers', 'Гироскоп', 'Акселерометр', 'Touchpad', 'Кастомизация'],
                ],
                [
                    'key' => 'output',
                    'type' => 'checkbox',
                    'label' => 'Вывод',
                    'options' => ['HDMI 2.1', 'HDMI 2.0', 'DisplayPort', 'USB-C', 'Wireless'],
                ],
                [
                    'key' => 'audio',
                    'type' => 'checkbox',
                    'label' => 'Аудио',
                    'options' => ['Stereo', 'Dolby Atmos', 'DTS:X', 'Tempest 3D Audio', 'Spatial Audio'],
                ],
                [
                    'key' => 'wifi',
                    'type' => 'checkbox',
                    'label' => 'Wi-Fi',
                    'options' => ['Нет', 'Wi-Fi 5', 'Wi-Fi 6', 'Wi-Fi 6E', 'Wi-Fi 7'],
                ],
                [
                    'key' => 'bluetooth',
                    'type' => 'checkbox',
                    'label' => 'Bluetooth',
                    'options' => ['Нет', 'Bluetooth 4.0', 'Bluetooth 5.0', 'Bluetooth 5.1', 'Bluetooth 5.2'],
                ],
                [
                    'key' => 'ethernet',
                    'type' => 'checkbox',
                    'label' => 'Ethernet',
                    'options' => ['Нет', '100Mbps', '1Gbps', '2.5Gbps', '10Gbps'],
                ],
                [
                    'key' => 'usb_ports',
                    'type' => 'checkbox',
                    'label' => 'USB порты',
                    'options' => ['USB-A', 'USB-C', 'USB 3.0', 'USB 3.2 Gen 1', 'USB 3.2 Gen 2'],
                ],
                [
                    'key' => 'vr_support',
                    'type' => 'checkbox',
                    'label' => 'Поддержка VR',
                    'options' => ['Нет', 'PlayStation VR', 'PlayStation VR2', 'Oculus Quest', 'HTC Vive', 'Valve Index'],
                ],
                [
                    'key' => 'backward_compatibility',
                    'type' => 'checkbox',
                    'label' => 'Обратная совместимость',
                    'options' => ['Нет', 'Предыдущее поколение', 'Все поколения'],
                ],
                [
                    'key' => 'game_pass',
                    'type' => 'checkbox',
                    'label' => 'Подписка на игры',
                    'options' => ['Нет', 'Xbox Game Pass', 'PlayStation Plus', 'Nintendo Switch Online'],
                ],
                [
                    'key' => 'online_multiplayer',
                    'type' => 'checkbox',
                    'label' => 'Онлайн мультиплеер',
                    ['options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'form_factor',
                    'type' => 'checkbox',
                    'label' => 'Форм-фактор',
                    'options' => ['Стационарная', 'Портативная', 'Гибридная'],
                ],
                [
                    'key' => 'screen',
                    'type' => 'checkbox',
                    'label' => 'Экран',
                    'options' => ['Нет', '6.2"', '6.7"', '7"', 'OLED', 'LCD', 'IPS'],
                ],
                [
                    'key' => 'weight',
                    'type' => 'checkbox',
                    'label' => 'Вес',
                    'options' => ['до 300г', '300-400г', '400-500г', '500-700г', '2-3кг', '3-5кг', '5кг+'],
                ],
                [
                    'key' => 'color',
                    'type' => 'checkbox',
                    'label' => 'Цвет',
                    'options' => ['Черный', 'Белый', 'Серый', 'Серебристый', 'Красный', 'Синий', 'Неон', 'Ограниченные серии'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
                ['value' => 'popularity', 'label' => 'По популярности'],
            ],
        );
    }

    private function getAudioFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::AUDIO->value,
            label: self::AUDIO->getLabel(),
            icon: self::AUDIO->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['JBL', 'Bose', 'Sony', 'Harman Kardon', 'Marshall', 'Yamaha', 'B&W'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'type',
                    'type' => 'checkbox',
                    'label' => 'Тип',
                    'options' => ['Портативная колонка', 'Стационарная', 'Саундбар', 'Домашний кинотеатр'],
                ],
                [
                    'key' => 'power',
                    'type' => 'checkbox',
                    'label' => 'Мощность',
                    'options' => ['10-20W', '20-50W', '50-100W', '100W+'],
                ],
                [
                    'key' => 'connection',
                    'type' => 'checkbox',
                    'label' => 'Подключение',
                    'options' => ['Bluetooth', 'Wi-Fi', 'AUX', 'USB'],
                ],
                [
                    'key' => 'battery',
                    'type' => 'checkbox',
                    'label' => 'Батарея',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'water_resistance',
                    'type' => 'checkbox',
                    'label' => 'Влагозащита',
                    'options' => ['IPX4', 'IPX5', 'IPX7', 'IP67'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
                ['value' => 'power', 'label' => 'По мощности'],
                ['value' => 'popular', 'label' => 'По популярности'],
            ],
        );
    }

    private function getNetworkingFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::NETWORKING->value,
            label: self::NETWORKING->getLabel(),
            icon: self::NETWORKING->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['TP-Link', 'ASUS', 'Netgear', 'Linksys', 'D-Link', 'Keenetic', 'Xiaomi'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'type',
                    'type' => 'checkbox',
                    'label' => 'Тип',
                    'options' => ['Роутер', 'Точка доступа', 'Коммутатор', 'Модум'],
                ],
                [
                    'key' => 'wifi_standard',
                    'type' => 'checkbox',
                    'label' => 'Стандарт Wi-Fi',
                    'options' => ['Wi-Fi 5 (802.11ac)', 'Wi-Fi 6 (802.11ax)', 'Wi-Fi 6E', 'Wi-Fi 7'],
                ],
                [
                    'key' => 'wifi_speed',
                    'type' => 'checkbox',
                    'label' => 'Скорость Wi-Fi',
                    'options' => ['1200 Мбит/с', '1800 Мбит/с', '2400 Мбит/с', '3000 Мбит/с', '5400 Мбит/с', '11000 Мбит/с'],
                ],
                [
                    'key' => 'lan_ports',
                    'type' => 'checkbox',
                    'label' => 'LAN порты',
                    'options' => ['1x1G', '2x1G', '4x1G', '2.5G', '10G'],
                ],
                [
                    'key' => 'wan_ports',
                    'type' => 'checkbox',
                    'label' => 'WAN порты',
                    'options' => ['1x1G', '2.5G', '10G', 'Dual WAN'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
                ['value' => 'speed', 'label' => 'По скорости'],
            ],
        );
    }

    private function getAccessoriesFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::ACCESSORIES->value,
            label: self::ACCESSORIES->getLabel(),
            icon: self::ACCESSORIES->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Apple', 'Samsung', 'Logitech', 'Razer', 'Anker', 'Baseus', 'Hoco'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'type',
                    'type' => 'checkbox',
                    'label' => 'Тип',
                    'options' => ['Зарядное устройство', 'Кабель', 'Чехол', 'Стекло', 'Подставка', 'Крепление'],
                ],
                [
                    'key' => 'compatibility',
                    'type' => 'checkbox',
                    'label' => 'Совместимость',
                    'options' => ['iPhone', 'Android', 'USB-C', 'Lightning', 'Универсальные'],
                ],
                [
                    'key' => 'color',
                    'type' => 'checkbox',
                    'label' => 'Цвет',
                    'options' => ['Черный', 'Белый', 'Прозрачный', 'Разноцветный'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
            ],
        );
    }

    private function getWearableFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::WEARABLE->value,
            label: self::WEARABLE->getLabel(),
            icon: self::WEARABLE->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Xiaomi', 'Huawei', 'Amazfit', 'Honor', 'Samsung', 'Garmin'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'type',
                    'type' => 'checkbox',
                    'label' => 'Тип',
                    'options' => ['Фитнес-браслет', 'Смарт-браслет', 'Трекер активности'],
                ],
                [
                    'key' => 'screen',
                    'type' => 'checkbox',
                    'label' => 'Экран',
                    'options' => ['AMOLED', 'OLED', 'LCD', 'Без экрана'],
                ],
                [
                    'key' => 'battery_life',
                    'type' => 'checkbox',
                    'label' => 'Автономность',
                    'options' => ['7 дней', '14 дней', '30 дней', '60 дней'],
                ],
                [
                    'key' => 'water_resistance',
                    'type' => 'checkbox',
                    'label' => 'Водозащита',
                    'options' => ['IP68', '5ATM', '50м'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
            ],
        );
    }

    private function getHomeAutomationFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::HOME_AUTOMATION->value,
            label: self::HOME_AUTOMATION->getLabel(),
            icon: self::HOME_AUTOMATION->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Xiaomi', 'Aqara', 'Philips Hue', 'Yeelight', 'Tuya', 'Sonoff', 'Shelly'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'type',
                    'type' => 'checkbox',
                    'label' => 'Тип устройства',
                    'options' => ['Умная лампа', 'Выключатель', 'Розетка', 'Датчик', 'Камера', 'Шлюз'],
                ],
                [
                    'key' => 'protocol',
                    'type' => 'checkbox',
                    'label' => 'Протокол',
                    'options' => ['Zigbee', 'Wi-Fi', 'Bluetooth', 'Matter', 'Z-Wave'],
                ],
                [
                    'key' => 'compatibility',
                    'type' => 'checkbox',
                    'label' => 'Совместимость',
                    'options' => ['Apple HomeKit', 'Google Home', 'Alexa', 'Yandex Alice', 'Mi Home'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
            ],
        );
    }

    private function getCarElectronicsFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::CAR_ELECTRONICS->value,
            label: self::CAR_ELECTRONICS->getLabel(),
            icon: self::CAR_ELECTRONICS->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Xiaomi', '70mai', 'BlackVue', 'Pioneer', 'Kenwood', 'Alpine'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'type',
                    'type' => 'checkbox',
                    'label' => 'Тип',
                    'options' => ['Видеорегистратор', 'Магнитола', 'Парктроник', 'Радар-детектор', 'Зарядка'],
                ],
                [
                    'key' => 'resolution',
                    'type' => 'checkbox',
                    'label' => 'Разрешение (для регистраторов)',
                    'options' => ['1080p', '2K', '4K'],
                ],
                [
                    'key' => 'gps',
                    'type' => 'checkbox',
                    'label' => 'GPS',
                    'options' => ['Да', 'Нет'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
            ],
        );
    }

    private function getAppliancesFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::APPLIANCES->value,
            label: self::APPLIANCES->getLabel(),
            icon: self::APPLIANCES->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Xiaomi', 'Dyson', 'Philips', 'Braun', 'Bosch', 'Tefal', 'Moulinex', 'Samsung', 'LG', 'Bork', 'Kitfort', 'Redmond', 'Polaris', 'Scarlett', 'Vitek'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'type',
                    'type' => 'checkbox',
                    'label' => 'Тип',
                    'options' => ['Пылесос', 'Утюг', 'Фен', 'Кофемашина', 'Мультиварка', 'Блендер', 'Парогенератор', 'Пароочиститель', 'Электрочайник', 'Тостер', 'Соковыжималка', 'Мясорубка', 'Хлебопечка', 'Йогуртница', 'Микроволновка', 'Духовка', 'Плита', 'Холодильник', 'Морозильник', 'Посудомоечная машина', 'Стиральная машина', 'Сушильная машина'],
                ],
                [
                    'key' => 'appliance_category',
                    'type' => 'checkbox',
                    'label' => 'Категория',
                    'options' => ['Кухонная техника', 'Климатическое оборудование', 'Для уборки', 'Для ухода за одеждой', 'Крупная бытовая техника'],
                ],
                [
                    'key' => 'power',
                    'type' => 'checkbox',
                    'label' => 'Мощность',
                    'options' => ['до 500W', '500-1000W', '1000-1500W', '1500-2000W', '2000W+'],
                ],
                [
                    'key' => 'volume',
                    'type' => 'checkbox',
                    'label' => 'Объём',
                    'options' => ['до 1л', '1-2л', '2-5л', '5-10л', '10л+'],
                ],
                [
                    'key' => 'capacity',
                    'type' => 'checkbox',
                    'label' => 'Вместимость',
                    'options' => ['до 5кг', '5-7кг', '7-9кг', '9-12кг', '12кг+'],
                ],
                [
                    'key' => 'tank_capacity',
                    'type' => 'checkbox',
                    'label' => 'Объём бака',
                    'options' => ['до 0.5л', '0.5-1л', '1-2л', '2-4л', '4л+'],
                ],
                [
                    'key' => 'suction_power',
                    'type' => 'checkbox',
                    'label' => 'Мощность всасывания',
                    'options' => ['до 200W', '200-300W', '300-400W', '400-500W', '500W+'],
                ],
                [
                    'key' => 'vacuum_type',
                    'type' => 'checkbox',
                    'label' => 'Тип пылесоса',
                    'options' => ['Робот-пылесос', 'Ручной', 'Вертикальный', 'С мешком', 'Циклонный'],
                ],
                [
                    'key' => 'dustbag_type',
                    'type' => 'checkbox',
                    'label' => 'Тип пылесборника',
                    'options' => ['Мешок', 'Контейнер', 'Циклонный'],
                ],
                [
                    'key' => 'filter_type',
                    'type' => 'checkbox',
                    'label' => 'Тип фильтра',
                    'options' => ['HEPA', 'HEPA 10', 'HEPA 11', 'HEPA 12', 'HEPA 13', 'HEPA 14', 'Аквафильтр', 'Циклонный'],
                ],
                [
                    'key' => 'cord_length',
                    'type' => 'checkbox',
                    'label' => 'Длина шнура',
                    'options' => ['до 5м', '5-7м', '7-10м', '10м+', 'Беспроводной'],
                ],
                [
                    'key' => 'battery_life',
                    'type' => 'checkbox',
                    'label' => 'Автономность',
                    'options' => ['до 30мин', '30-60мин', '60-90мин', '90-120мин', '120мин+'],
                ],
                [
                    'key' => 'iron_soleplate',
                    'type' => 'checkbox',
                    'label' => 'Подошва утюга',
                    'options' => ['Алюминий', 'Керамика', 'Тефлон', 'Сталь', 'Титан', 'Сапфир'],
                ],
                [
                    'key' => 'steam_output',
                    'type' => 'checkbox',
                    'label' => 'Подача пара',
                    'options' => ['до 30г/мин', '30-50г/мин', '50-80г/мин', '80-120г/мин', '120г/мин+'],
                ],
                [
                    'key' => 'iron_weight',
                    'type' => 'checkbox',
                    'label' => 'Вес утюга',
                    'options' => ['до 1кг', '1-1.5кг', '1.5-2кг', '2кг+'],
                ],
                [
                    'key' => 'coffee_type',
                    'type' => 'checkbox',
                    'label' => 'Тип кофемашины',
                    'options' => ['Капсульная', 'Рожковая', 'Капельная', 'Комбинированная', 'Эспрессо', 'Автоматическая'],
                ],
                [
                    'key' => 'pressure',
                    'type' => 'checkbox',
                    'label' => 'Давление',
                    'options' => ['до 10 бар', '10-15 бар', '15-19 бар', '19 бар+'],
                ],
                [
                    'key' => 'water_tank',
                    'type' => 'checkbox',
                    'label' => 'Объём резервуара',
                    'options' => ['до 1л', '1-1.5л', '1.5-2л', '2-3л', '3л+'],
                ],
                [
                    'key' => 'coffee_grinder',
                    'type' => 'checkbox',
                    'label' => 'Кофемолка',
                    'options' => ['Нет', 'Встроенная', 'Отдельная'],
                ],
                [
                    'key' => 'blender_type',
                    'type' => 'checkbox',
                    'label' => 'Тип блендера',
                    'options' => ['Погружной', 'Стационарный', 'Комбинированный'],
                ],
                [
                    'key' => 'blender_power',
                    'type' => 'checkbox',
                    'label' => 'Мощность блендера',
                    'options' => ['до 500W', '500-800W', '800-1200W', '1200-1500W', '1500W+'],
                ],
                [
                    'key' => 'speed_modes',
                    'type' => 'checkbox',
                    'label' => 'Режимов скорости',
                    'options' => ['1-2', '3-5', '6-10', '10+'],
                ],
                [
                    'key' => 'turbo_mode',
                    'type' => 'checkbox',
                    'label' => 'Турбо-режим',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'ice_crushing',
                    'type' => 'checkbox',
                    'label' => 'Измельчение льда',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'material_jar',
                    'type' => 'checkbox',
                    'label' => 'Материал чаши',
                    'options' => ['Пластик', 'Стекло', 'Металл'],
                ],
                [
                    'key' => 'jar_capacity',
                    'type' => 'checkbox',
                    'label' => 'Объём чаши',
                    'options' => ['до 0.5л', '0.5-1л', '1-1.5л', '1.5-2л', '2л+'],
                ],
                [
                    'key' => 'cooking_programs',
                    'type' => 'checkbox',
                    'label' => 'Программ приготовления',
                    'options' => ['до 5', '5-10', '10-20', '20-30', '30+'],
                ],
                [
                    'key' => 'delayed_start',
                    'type' => 'checkbox',
                    'label' => 'Отложенный старт',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'keep_warm',
                    'type' => 'checkbox',
                    'label' => 'Подогрев',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'control_type',
                    'type' => 'checkbox',
                    'label' => 'Тип управления',
                    'options' => ['Механическое', 'Электронное', 'Сенсорное', 'С пульта', 'Через приложение'],
                ],
                [
                    'key' => 'display',
                    'type' => 'checkbox',
                    'label' => 'Дисплей',
                    'options' => ['Нет', 'LED', 'LCD', 'OLED', 'Сенсорный'],
                ],
                [
                    'key' => 'timer',
                    'type' => 'checkbox',
                    'label' => 'Таймер',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'child_lock',
                    'type' => 'checkbox',
                    'label' => 'Защита от детей',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'auto_off',
                    'type' => 'checkbox',
                    'label' => 'Автоотключение',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'noise_level',
                    'type' => 'checkbox',
                    'label' => 'Уровень шума',
                    'options' => ['до 50дБ', '50-60дБ', '60-70дБ', '70-80дБ', '80дБ+'],
                ],
                [
                    'key' => 'energy_class',
                    'type' => 'checkbox',
                    'label' => 'Класс энергоэффективности',
                    'options' => ['A+++', 'A++', 'A+', 'A', 'B', 'C', 'D'],
                ],
                [
                    'key' => 'color',
                    'type' => 'checkbox',
                    'label' => 'Цвет',
                    'options' => ['Черный', 'Белый', 'Серый', 'Серебристый', 'Красный', 'Бежевый', 'Инокс'],
                ],
                [
                    'key' => 'material',
                    'type' => 'checkbox',
                    'label' => 'Материал',
                    'options' => ['Пластик', 'Металл', 'Стекло', 'Керамика', 'Нержавеющая сталь'],
                ],
                [
                    'key' => 'weight',
                    'type' => 'checkbox',
                    'label' => 'Вес',
                    'options' => ['до 2кг', '2-5кг', '5-10кг', '10-20кг', '20кг+'],
                ],
                [
                    'key' => 'dimensions',
                    'type' => 'checkbox',
                    'label' => 'Размеры',
                    'options' => ['Компактные', 'Средние', 'Крупные'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
                ['value' => 'power', 'label' => 'По мощности'],
                ['value' => 'popular', 'label' => 'По популярности'],
            ],
        );
    }

    private function getBeautyDevicesFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::BEAUTY_DEVICES->value,
            label: self::BEAUTY_DEVICES->getLabel(),
            icon: self::BEAUTY_DEVICES->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Philips', 'Braun', 'Panasonic', 'Babyliss', 'Remington', 'Dyson', 'BaByliss Pro', 'Rowenta', 'Valera', 'Moser', 'Andis', 'Wahl'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'type',
                    'type' => 'checkbox',
                    'label' => 'Тип устройства',
                    'options' => ['Фен', 'Выпрямитель', 'Щипцы для завивки', 'Стайлер', 'Бигуди', 'Укладочная щетка', 'Плойка', 'Мультистайлер'],
                ],
                [
                    'key' => 'power',
                    'type' => 'checkbox',
                    'label' => 'Мощность',
                    'options' => ['до 1000W', '1000-1500W', '1500-2000W', '2000-2500W', '2500W+'],
                ],
                [
                    'key' => 'temperature',
                    'type' => 'checkbox',
                    'label' => 'Температура',
                    'options' => ['до 150°C', '150-180°C', '180-200°C', '200-230°C', '230°C+'],
                ],
                [
                    'key' => 'ionization',
                    'type' => 'checkbox',
                    'label' => 'Ионизация',
                    'options' => ['Нет', 'Да'],
                ],
                [
                    'key' => 'plate_width',
                    'type' => 'checkbox',
                    'label' => 'Ширина пластин',
                    'options' => ['до 20мм', '20-25мм', '25-32мм', '32-45мм', '45мм+'],
                ],
                [
                    'key' => 'wireless',
                    'type' => 'checkbox',
                    'label' => 'Беспроводное',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'color',
                    'type' => 'checkbox',
                    'label' => 'Цвет',
                    'options' => ['Черный', 'Белый', 'Розовый', 'Серебристый', 'Золотой'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
                ['value' => 'power', 'label' => 'По мощности'],
                ['value' => 'popular', 'label' => 'По популярности'],
            ],
        );
    }

    private function getMassageDevicesFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::MASSAGE_DEVICES->value,
            label: self::MASSAGE_DEVICES->getLabel(),
            icon: self::MASSAGE_DEVICES->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Medisana', 'Beurer', 'Breuer', 'Panasonic', 'Xiaomi', 'HoMedics', 'Casada', 'OGAWA', 'OSIM', 'Inada'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'type',
                    'type' => 'checkbox',
                    'label' => 'Тип массажёра',
                    'options' => ['Массажёр для шеи', 'Массажёр для спины', 'Массажёр для ног', 'Массажёр для плеч', 'Массажёр для рук', 'Перкуссионный', 'Вибрационный', 'Роликовый', 'Шиацу'],
                ],
                [
                    'key' => 'massage_technique',
                    'type' => 'checkbox',
                    'label' => 'Техника массажа',
                    'options' => ['Вибрация', 'Шиацу', 'Перкуссия', 'Роликовый', 'Акупрессура', 'Комбинированный'],
                ],
                [
                    'key' => 'heat',
                    'type' => 'checkbox',
                    'label' => 'Подогрев',
                    'options' => ['Нет', 'Да'],
                ],
                [
                    'key' => 'battery_life',
                    'type' => 'checkbox',
                    'label' => 'Автономность',
                    'options' => ['Беспроводной', 'до 30 мин', '30-60 мин', '60-120 мин', '120+ мин'],
                ],
                [
                    'key' => 'portable',
                    'type' => 'checkbox',
                    'label' => 'Портативный',
                    'options' => ['Да', 'Нет'],
                ],
                [
                    'key' => 'weight',
                    'type' => 'checkbox',
                    'label' => 'Вес',
                    'options' => ['до 1кг', '1-2кг', '2-5кг', '5-10кг', '10кг+'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
                ['value' => 'popular', 'label' => 'По популярности'],
            ],
        );
    }

    private function getSoftwareFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::SOFTWARE->value,
            label: self::SOFTWARE->getLabel(),
            icon: self::SOFTWARE->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд/Разработчик',
                    'options' => ['Microsoft', 'Adobe', 'Corel', 'Autodesk', 'Kaspersky', 'ESET', 'Dr.Web', 'Norton', 'McAfee', 'Bitdefender', 'ABBYY', 'Яндекс', '1C', 'Гарант', 'КонсультантПлюс'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'license_type',
                    'type' => 'checkbox',
                    'label' => 'Тип лицензии',
                    'options' => ['Digital (ключ)', 'Box (коробка)', 'OEM', 'Volume', 'Subscription', 'Freemium', 'Open Source'],
                ],
                [
                    'key' => 'license_duration',
                    'type' => 'checkbox',
                    'label' => 'Срок действия',
                    'options' => ['Пожизненная', '1 год', '2 года', '3 года', 'Подписка (месяц/год)'],
                ],
                [
                    'key' => 'devices',
                    'type' => 'checkbox',
                    'label' => 'Количество устройств',
                    'options' => ['1 устройство', '2 устройства', '3-5 устройств', '5-10 устройств', 'Безлимитный'],
                ],
                [
                    'key' => 'platform',
                    'type' => 'checkbox',
                    'label' => 'Платформа',
                    'options' => ['Windows', 'macOS', 'Linux', 'Android', 'iOS', 'Web', 'Кроссплатформенный'],
                ],
                [
                    'key' => 'category',
                    'type' => 'checkbox',
                    'label' => 'Категория',
                    'options' => ['ОС', 'Антивирус', 'Офисное', 'Графика и дизайн', 'Видео и аудио', '3D и CAD', 'Образование', 'Разработка', 'Бизнес', 'Утилиты', 'Игры'],
                ],
                [
                    'key' => 'language',
                    'type' => 'checkbox',
                    'label' => 'Язык интерфейса',
                    'options' => ['Русский', 'Английский', 'Мультиязычный'],
                ],
                [
                    'key' => 'cloud_services',
                    'type' => 'checkbox',
                    'label' => 'Облачные сервисы',
                    'options' => ['Нет', 'Хранилище', 'Синхронизация', 'Коллаборация', 'Полный пакет'],
                ],
                [
                    'key' => 'support',
                    'type' => 'checkbox',
                    'label' => 'Техподдержка',
                    'options' => ['Нет', 'Email', 'Телефон', 'Чат', '24/7', 'VIP'],
                ],
                [
                    'key' => 'delivery_type',
                    'type' => 'checkbox',
                    'label' => 'Способ получения',
                    'options' => ['Электронный ключ', 'Физический носитель', 'Активация по телефону', 'Загрузка с сайта'],
                ],
                [
                    'key' => 'trial',
                    'type' => 'checkbox',
                    'label' => 'Пробная версия',
                    'options' => ['Да', 'Нет'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
                ['value' => 'popular', 'label' => 'По популярности'],
                ['value' => 'newest', 'label' => 'Сначала новинки'],
            ],
        );
    }

    // Helper methods for new categories
    private function getGenericFilterConfig(string $type, string $label, string $icon): FilterConfigDto
    {
        return new FilterConfigDto(
            type: $type,
            label: $label,
            icon: $icon,
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Xiaomi', 'Samsung', 'Apple', 'Sony', 'LG', 'Philips'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'rating',
                    'type' => 'checkbox',
                    'label' => 'Рейтинг',
                    'options' => ['4+', '4.5+', '5'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
            ],
        );
    }

    private function getDesktopsFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::DESKTOPS->value,
            label: self::DESKTOPS->getLabel(),
            icon: self::DESKTOPS->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['ASUS', 'MSI', 'Dell', 'HP', 'Lenovo', 'Acer'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'cpu',
                    'type' => 'checkbox',
                    'label' => 'Процессор',
                    'options' => ['Intel Core i5', 'Intel Core i7', 'AMD Ryzen 5', 'AMD Ryzen 7'],
                ],
                [
                    'key' => 'ram',
                    'type' => 'checkbox',
                    'label' => 'Оперативная память',
                    'options' => ['8GB', '16GB', '32GB', '64GB'],
                ],
                [
                    'key' => 'storage',
                    'type' => 'checkbox',
                    'label' => 'Накопитель',
                    'options' => ['256GB SSD', '512GB SSD', '1TB SSD', '2TB SSD'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
            ],
        );
    }

    private function getMonitorsFilterConfig(): FilterConfigDto
    {
        return new FilterConfigDto(
            type: self::MONITORS->value,
            label: self::MONITORS->getLabel(),
            icon: self::MONITORS->getIcon(),
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['LG', 'Samsung', 'Dell', 'ASUS', 'AOC', 'Philips'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'screen_size',
                    'type' => 'checkbox',
                    'label' => 'Диагональ',
                    'options' => ['24"', '27"', '32"', '34"'],
                ],
                [
                    'key' => 'resolution',
                    'type' => 'checkbox',
                    'label' => 'Разрешение',
                    'options' => ['1920x1080', '2560x1440', '3840x2160', '5120x1440'],
                ],
                [
                    'key' => 'panel_type',
                    'type' => 'checkbox',
                    'label' => 'Тип матрицы',
                    'options' => ['IPS', 'VA', 'TN', 'OLED'],
                ],
                [
                    'key' => 'refresh_rate',
                    'type' => 'checkbox',
                    'label' => 'Герцовка',
                    'options' => ['60Hz', '75Hz', '144Hz', '165Hz', '240Hz'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
            ],
        );
    }

    private function getComponentsFilterConfig(string $type, string $label): FilterConfigDto
    {
        return new FilterConfigDto(
            type: $type,
            label: $label,
            icon: 'cpu',
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Intel', 'AMD', 'NVIDIA', 'ASUS', 'MSI', 'Gigabyte', 'Corsair'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'compatibility',
                    'type' => 'checkbox',
                    'label' => 'Совместимость',
                    'options' => ['Intel', 'AMD', 'Universal'],
                ],
                [
                    'key' => 'rating',
                    'type' => 'checkbox',
                    'label' => 'Рейтинг',
                    'options' => ['4+', '4.5+', '5'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
            ],
        );
    }

    private function getPeripheralsFilterConfig(string $type, string $label): FilterConfigDto
    {
        return new FilterConfigDto(
            type: $type,
            label: $label,
            icon: 'keyboard',
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Logitech', 'Razer', 'Corsair', 'SteelSeries', 'HyperX'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'connection',
                    'type' => 'checkbox',
                    'label' => 'Подключение',
                    'options' => ['USB', 'Wireless', 'Bluetooth'],
                ],
                [
                    'key' => 'rating',
                    'type' => 'checkbox',
                    'label' => 'Рейтинг',
                    'options' => ['4+', '4.5+', '5'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
            ],
        );
    }

    private function getSmartHomeFilterConfig(string $type, string $label, string $icon): FilterConfigDto
    {
        return new FilterConfigDto(
            type: $type,
            label: $label,
            icon: $icon,
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Xiaomi', 'Philips Hue', 'Yandex', 'Aqara', 'Tuya'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'protocol',
                    'type' => 'checkbox',
                    'label' => 'Протокол',
                    'options' => ['Wi-Fi', 'Zigbee', 'Bluetooth', 'Thread'],
                ],
                [
                    'key' => 'ecosystem',
                    'type' => 'checkbox',
                    'label' => 'Экосистема',
                    'options' => ['Yandex Alice', 'Google Home', 'Apple HomeKit', 'Xiaomi Mi Home'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
            ],
        );
    }
}
            secondaryFilters: [
                [
                    'key' => 'compatibility',
                    'type' => 'checkbox',
                    'label' => 'Совместимость',
                    'options' => ['Intel', 'AMD', 'Universal'],
                ],
                [
                    'key' => 'rating',
                    'type' => 'checkbox',
                    'label' => 'Рейтинг',
                    'options' => ['4+', '4.5+', '5'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
            ],
        );
    }

    private function getPeripheralsFilterConfig(string $type, string $label): FilterConfigDto
    {
        return new FilterConfigDto(
            type: $type,
            label: $label,
            icon: 'keyboard',
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Logitech', 'Razer', 'Corsair', 'SteelSeries', 'HyperX'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'connection',
                    'type' => 'checkbox',
                    'label' => 'Подключение',
                    'options' => ['USB', 'Wireless', 'Bluetooth'],
                ],
                [
                    'key' => 'rating',
                    'type' => 'checkbox',
                    'label' => 'Рейтинг',
                    'options' => ['4+', '4.5+', '5'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
            ],
        );
    }

    private function getSmartHomeFilterConfig(string $type, string $label, string $icon): FilterConfigDto
    {
        return new FilterConfigDto(
            type: $type,
            label: $label,
            icon: $icon,
            primaryFilters: [
                [
                    'key' => 'price_range',
                    'type' => 'range',
                    'label' => 'Цена',
                    'unit' => '₽',
                ],
                [
                    'key' => 'brand',
                    'type' => 'checkbox',
                    'label' => 'Бренд',
                    'options' => ['Xiaomi', 'Philips Hue', 'Yandex', 'Aqara', 'Tuya'],
                ],
            ],
            secondaryFilters: [
                [
                    'key' => 'protocol',
                    'type' => 'checkbox',
                    'label' => 'Протокол',
                    'options' => ['Wi-Fi', 'Zigbee', 'Bluetooth', 'Thread'],
                ],
                [
                    'key' => 'ecosystem',
                    'type' => 'checkbox',
                    'label' => 'Экосистема',
                    'options' => ['Yandex Alice', 'Google Home', 'Apple HomeKit', 'Xiaomi Mi Home'],
                ],
            ],
            sortOptions: [
                ['value' => 'relevance', 'label' => 'По релевантности'],
                ['value' => 'price_asc', 'label' => 'Сначала дешевле'],
                ['value' => 'price_desc', 'label' => 'Сначала дороже'],
                ['value' => 'rating', 'label' => 'По рейтингу'],
            ],
        );
    }
}
