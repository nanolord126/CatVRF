<?php

declare(strict_types=1);

namespace App\Domains\Common\Domain\Services;

use App\Domains\Common\Domain\Entities\Brand;
use App\Domains\Common\Domain\Repositories\BrandRepositoryInterface;
use Illuminate\Support\Collection;

final readonly class BrandService
{
    public function __construct(
        private BrandRepositoryInterface $brandRepository,
    ) {}

    public function createBrand(
        string $name,
        ?string $slug = null,
        ?string $logo = null,
        ?string $website = null,
        ?string $description = null,
        ?array $metadata = null,
    ): Brand {
        if ($slug !== null && $this->brandRepository->existsBySlug($slug)) {
            throw new \InvalidArgumentException('Brand with this slug already exists');
        }

        $brand = Brand::create(
            name: $name,
            slug: $slug,
            logo: $logo,
            website: $website,
            description: $description,
            metadata: $metadata,
        );

        return $this->brandRepository->save($brand);
    }

    public function updateBrand(
        int $brandId,
        ?string $name = null,
        ?string $slug = null,
        ?string $logo = null,
        ?string $website = null,
        ?string $description = null,
        ?array $metadata = null,
    ): Brand {
        $brand = $this->getBrandById($brandId);

        if ($slug !== null && $slug !== $brand->slug && $this->brandRepository->existsBySlug($slug)) {
            throw new \InvalidArgumentException('Brand with this slug already exists');
        }

        return $this->brandRepository->save($brand);
    }

    public function activateBrand(int $brandId): Brand
    {
        $brand = $this->getBrandById($brandId);

        $activatedBrand = $brand->activate();

        return $this->brandRepository->save($activatedBrand);
    }

    public function deactivateBrand(int $brandId): Brand
    {
        $brand = $this->getBrandById($brandId);

        $deactivatedBrand = $brand->deactivate();

        return $this->brandRepository->save($deactivatedBrand);
    }

    public function verifyBrand(int $brandId): Brand
    {
        $brand = $this->getBrandById($brandId);

        $verifiedBrand = $brand->verify();

        return $this->brandRepository->save($verifiedBrand);
    }

    public function unverifyBrand(int $brandId): Brand
    {
        $brand = $this->getBrandById($brandId);

        $unverifiedBrand = $brand->unverify();

        return $this->brandRepository->save($unverifiedBrand);
    }

    public function getBrandById(int $id): Brand
    {
        $brand = $this->brandRepository->findById($id);

        if ($brand === null) {
            throw new \InvalidArgumentException('Brand not found');
        }

        return $brand;
    }

    public function getBrandBySlug(string $slug): Brand
    {
        $brand = $this->brandRepository->findBySlug($slug);

        if ($brand === null) {
            throw new \InvalidArgumentException('Brand not found');
        }

        return $brand;
    }

    public function searchBrands(string $query, array $filters = [], int $limit = 50): Collection
    {
        return $this->brandRepository->search($query, $filters, $limit);
    }

    public function getActiveBrands(array $filters = [], int $limit = 100): Collection
    {
        return $this->brandRepository->findActive($filters, $limit);
    }

    public function getVerifiedBrands(array $filters = [], int $limit = 100): Collection
    {
        return $this->brandRepository->findVerified($filters, $limit);
    }

    public function deleteBrand(int $id): bool
    {
        return $this->brandRepository->delete($id);
    }
}
