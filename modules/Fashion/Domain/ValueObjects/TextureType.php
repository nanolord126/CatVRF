<?php

declare(strict_types=1);

namespace Modules\Fashion\Domain\ValueObjects;

enum TextureType: string
{
    case FABRIC = 'fabric';
    case LEATHER = 'leather';
    case DENIM = 'denim';
    case KNITWEAR = 'knitwear';
    case SILK = 'silk';
    case SYNTHETIC = 'synthetic';
    case WOOL = 'wool';
    case LINEN = 'linen';
    case VELVET = 'velvet';
    case SUEDE = 'suede';
    case CANVAS = 'canvas';
    case TECHNICAL = 'technical';
    case MESH = 'mesh';
    case RUBBER = 'rubber';
    case PLASTIC = 'plastic';

    public function getPromptKeywords(): array
    {
        return match ($this) {
            self::FABRIC => ['detailed fabric weave', 'realistic cloth texture', 'natural fibers'],
            self::LEATHER => ['genuine leather texture', 'visible pores', 'natural wrinkles', 'stitching details'],
            self::DENIM => ['detailed denim weave', 'realistic cotton texture', 'jeans fabric', 'indigo dye'],
            self::KNITWEAR => ['cable knit', 'ribbed texture', 'soft wool', 'knitted pattern', 'yarn detail'],
            self::SILK => ['smooth silk', 'sheen fabric', 'luxurious drape', 'natural sheen'],
            self::SYNTHETIC => ['synthetic fabric', 'polyester texture', 'technical material', 'performance fabric'],
            self::WOOL => ['wool texture', 'natural fibers', 'warm fabric', 'fleece detail'],
            self::LINEN => ['linen texture', 'natural weave', 'breathable fabric', 'organic fibers'],
            self::VELVET => ['velvet texture', 'soft pile', 'luxurious fabric', 'rich depth'],
            self::SUEDE => ['suede texture', 'soft napped finish', 'leather grain', 'matte surface'],
            self::CANVAS => ['canvas texture', 'heavy fabric', 'woven pattern', 'durable material'],
            self::TECHNICAL => ['technical fabric', 'mesh panels', 'breathable material', 'sportswear texture'],
            self::MESH => ['mesh texture', 'perforated fabric', 'breathable pattern', 'grid structure'],
            self::RUBBER => ['rubber texture', 'synthetic material', 'flexible surface', 'grip pattern'],
            self::PLASTIC => ['plastic texture', 'synthetic material', 'smooth surface', 'molded detail'],
        };
    }

    public function getRecommendedLoras(): array
    {
        return match ($this) {
            self::FABRIC => ['FabricTextile:0.72', 'FashionRealism_v2:0.78'],
            self::LEATHER => ['LeatherMaterials:0.82', 'FashionRealism_v2:0.78', 'GarmentConstruction:0.68'],
            self::DENIM => ['FabricTextile:0.85', 'FashionRealism_v2:0.78', 'DenimSpecialized:0.75'],
            self::KNITWEAR => ['FabricTextile:0.78', 'KnitwearSpecialized:0.78', 'FashionRealism_v2:0.75'],
            self::SILK => ['LuxuryFabrics:0.80', 'FashionRealism_v2:0.78'],
            self::SYNTHETIC => ['TechnicalFabric:0.70', 'FashionRealism_v2:0.75'],
            self::WOOL => ['FabricTextile:0.75', 'WoolTexture:0.72', 'FashionRealism_v2:0.75'],
            self::LINEN => ['NaturalFabrics:0.70', 'FabricTextile:0.68'],
            self::VELVET => ['LuxuryFabrics:0.82', 'FashionRealism_v2:0.78'],
            self::SUEDE => ['LeatherMaterials:0.80', 'FashionRealism_v2:0.78'],
            self::CANVAS => ['FabricTextile:0.70', 'HeavyFabrics:0.68'],
            self::TECHNICAL => ['TechnicalFabric:0.75', 'SportswearTexture:0.70'],
            self::MESH => ['TechnicalFabric:0.72', 'MeshTexture:0.70'],
            self::RUBBER => ['SyntheticMaterials:0.75', 'FashionRealism_v2:0.70'],
            self::PLASTIC => ['SyntheticMaterials:0.72', 'FashionRealism_v2:0.70'],
        };
    }
}
