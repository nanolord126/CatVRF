<?php

declare(strict_types=1);

namespace Modules\Fashion\Domain\DTOs;

use Illuminate\Support\Str;
use Modules\Fashion\Domain\ValueObjects\TextureType;
use InvalidArgumentException;

final readonly class GenerateTextureDTO
{
    private function __construct(
        public int $model3dId,
        public string $productName,
        public TextureType $materialType,
        public array $photoPaths,
        public ?string $customPrompt,
        public ?string $customNegativePrompt,
        public int $width,
        public int $height,
        public int $steps,
        public float $cfgScale,
        public string $sampler,
        public int $seed,
        public bool $useControlNet,
        public bool $useIpAdapter,
        public array $loraWeights,
        public array $controlNetConfig,
        public ?string $correlationId,
    ) {}

    public static function fromArray(array $data): self
    {
        self::validate($data);

        return new self(
            model3dId: (int) $data['model3d_id'],
            productName: (string) $data['product_name'],
            materialType: TextureType::from((string) $data['material_type']),
            photoPaths: array_map('strval', $data['photo_paths']),
            customPrompt: $data['custom_prompt'] ?? null,
            customNegativePrompt: $data['custom_negative_prompt'] ?? null,
            width: (int) ($data['width'] ?? 1024),
            height: (int) ($data['height'] ?? 1024),
            steps: (int) ($data['steps'] ?? 50),
            cfgScale: (float) ($data['cfg_scale'] ?? 7.5),
            sampler: (string) ($data['sampler'] ?? 'DPM++ 2M Karras'),
            seed: (int) ($data['seed'] ?? -1),
            useControlNet: (bool) ($data['use_controlnet'] ?? true),
            useIpAdapter: (bool) ($data['use_ip_adapter'] ?? true),
            loraWeights: (array) ($data['lora_weights'] ?? []),
            controlNetConfig: (array) ($data['controlnet_config'] ?? self::getDefaultControlNetConfig()),
            correlationId: $data['correlation_id'] ?? (string) Str::uuid(),
        );
    }

    public static function create(
        int $model3dId,
        string $productName,
        TextureType $materialType,
        array $photoPaths,
        ?string $customPrompt = null,
    ): self {
        if (empty($photoPaths)) {
            throw new InvalidArgumentException('At least one photo path is required');
        }

        if (count($photoPaths) > 12) {
            throw new InvalidArgumentException('Maximum 12 photos allowed');
        }

        return new self(
            model3dId: $model3dId,
            productName: $productName,
            materialType: $materialType,
            photoPaths: $photoPaths,
            customPrompt: $customPrompt,
            customNegativePrompt: null,
            width: 1024,
            height: 1024,
            steps: 50,
            cfgScale: 7.5,
            sampler: 'DPM++ 2M Karras',
            seed: -1,
            useControlNet: true,
            useIpAdapter: true,
            loraWeights: $materialType->getRecommendedLoras(),
            controlNetConfig: self::getDefaultControlNetConfig(),
            correlationId: (string) Str::uuid(),
        );
    }

    private static function validate(array $data): void
    {
        if (!isset($data['model3d_id']) || !is_numeric($data['model3d_id'])) {
            throw new InvalidArgumentException('model3d_id is required and must be numeric');
        }

        if (!isset($data['product_name']) || empty($data['product_name'])) {
            throw new InvalidArgumentException('product_name is required and cannot be empty');
        }

        if (!isset($data['material_type'])) {
            throw new InvalidArgumentException('material_type is required');
        }

        if (!TextureType::tryFrom((string) $data['material_type'])) {
            throw new InvalidArgumentException('Invalid material_type value');
        }

        if (!isset($data['photo_paths']) || !is_array($data['photo_paths'])) {
            throw new InvalidArgumentException('photo_paths is required and must be an array');
        }

        if (empty($data['photo_paths'])) {
            throw new InvalidArgumentException('At least one photo path is required');
        }

        if (count($data['photo_paths']) > 12) {
            throw new InvalidArgumentException('Maximum 12 photos allowed');
        }

        if (isset($data['width']) && ($data['width'] < 512 || $data['width'] > 2048)) {
            throw new InvalidArgumentException('width must be between 512 and 2048');
        }

        if (isset($data['height']) && ($data['height'] < 512 || $data['height'] > 2048)) {
            throw new InvalidArgumentException('height must be between 512 and 2048');
        }

        if (isset($data['steps']) && ($data['steps'] < 20 || $data['steps'] > 100)) {
            throw new InvalidArgumentException('steps must be between 20 and 100');
        }

        if (isset($data['cfg_scale']) && ($data['cfg_scale'] < 1.0 || $data['cfg_scale'] > 20.0)) {
            throw new InvalidArgumentException('cfg_scale must be between 1.0 and 20.0');
        }
    }

    private static function getDefaultControlNetConfig(): array
    {
        return [
            'canny' => [
                'model' => 'control_v11p_sd15_canny',
                'weight' => 0.85,
                'guidance_start' => 0.0,
                'guidance_end' => 1.0,
            ],
            'depth' => [
                'model' => 'control_v11f1p_sd15_depth',
                'weight' => 0.70,
                'guidance_start' => 0.0,
                'guidance_end' => 1.0,
            ],
            'openpose' => [
                'model' => 'control_v11p_sd15_openpose',
                'weight' => 0.70,
                'guidance_start' => 0.0,
                'guidance_end' => 1.0,
            ],
        ];
    }

    public function getPrompt(): string
    {
        if ($this->customPrompt) {
            return $this->customPrompt;
        }

        $basePrompt = "high quality product photography of {$this->productName}, ";
        $basePrompt .= implode(', ', $this->materialType->getPromptKeywords());
        $basePrompt .= ", detailed fabric weave, realistic material, studio lighting, 8k, product shot, white background";

        return $basePrompt;
    }

    public function getNegativePrompt(): string
    {
        if ($this->customNegativePrompt) {
            return $this->customNegativePrompt;
        }

        return "lowres, bad anatomy, bad hands, text, error, missing fingers, extra digit, fewer digits, cropped, worst quality, low quality, normal quality, jpeg artifacts, signature, watermark, username, blurry, deformed, ugly, plastic skin, doll-like, disfigured, poorly drawn face, mutation, mutated, extra limb, ugly, poorly drawn hands, missing limb, floating limbs, disconnected limbs, malformed hands, blur, out of focus, long neck, long body, disgusting, childish, cartoon, 3d, disfigured, bad art, deformed, watermark, out of frame";
    }

    public function getReferenceImagePath(): string
    {
        return $this->photoPaths[0] ?? throw new InvalidArgumentException('No reference image available');
    }

    public function withSeed(int $seed): self
    {
        return new self(
            model3dId: $this->model3dId,
            productName: $this->productName,
            materialType: $this->materialType,
            photoPaths: $this->photoPaths,
            customPrompt: $this->customPrompt,
            customNegativePrompt: $this->customNegativePrompt,
            width: $this->width,
            height: $this->height,
            steps: $this->steps,
            cfgScale: $this->cfgScale,
            sampler: $this->sampler,
            seed: $seed,
            useControlNet: $this->useControlNet,
            useIpAdapter: $this->useIpAdapter,
            loraWeights: $this->loraWeights,
            controlNetConfig: $this->controlNetConfig,
            correlationId: $this->correlationId,
        );
    }

    public function toArray(): array
    {
        return [
            'model3d_id' => $this->model3dId,
            'product_name' => $this->productName,
            'material_type' => $this->materialType->value,
            'photo_paths' => $this->photoPaths,
            'custom_prompt' => $this->customPrompt,
            'custom_negative_prompt' => $this->customNegativePrompt,
            'width' => $this->width,
            'height' => $this->height,
            'steps' => $this->steps,
            'cfg_scale' => $this->cfgScale,
            'sampler' => $this->sampler,
            'seed' => $this->seed,
            'use_controlnet' => $this->useControlNet,
            'use_ip_adapter' => $this->useIpAdapter,
            'lora_weights' => $this->loraWeights,
            'controlnet_config' => $this->controlNetConfig,
            'correlation_id' => $this->correlationId,
            'prompt' => $this->getPrompt(),
            'negative_prompt' => $this->getNegativePrompt(),
        ];
    }

    public function getEstimatedGenerationTime(): int
    {
        $baseTime = 180; // 3 minutes base
        $timePerStep = 2; // 2 seconds per step
        $timePerPhoto = 15; // 15 seconds per photo for preprocessing

        $totalTime = $baseTime
            + ($this->steps * $timePerStep)
            + (count($this->photoPaths) * $timePerPhoto);

        if ($this->useControlNet) {
            $totalTime += 60; // Additional time for ControlNet
        }

        if ($this->useIpAdapter) {
            $totalTime += 30; // Additional time for IP-Adapter
        }

        return $totalTime;
    }
}
