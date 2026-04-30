<?php

declare(strict_types=1);

namespace App\Domains\Common\Domain\Services;

use App\Domains\Common\Domain\Entities\Category;
use App\Domains\Common\Domain\Repositories\CategoryRepositoryInterface;
use Illuminate\Support\Collection;

final readonly class CategoryService
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
    ) {}

    public function createCategory(
        string $name,
        ?string $slug = null,
        ?string $description = null,
        ?int $parentId = null,
        int $sortOrder = 0,
        ?string $icon = null,
        ?string $image = null,
        ?array $metadata = null,
    ): Category {
        if ($slug !== null && $this->categoryRepository->existsBySlug($slug)) {
            throw new \InvalidArgumentException('Category with this slug already exists');
        }

        if ($parentId !== null) {
            $parent = $this->categoryRepository->findById($parentId);
            if ($parent === null) {
                throw new \InvalidArgumentException('Parent category not found');
            }
        }

        $category = Category::create(
            name: $name,
            slug: $slug,
            description: $description,
            parentId: $parentId,
            sortOrder: $sortOrder,
            icon: $icon,
            image: $image,
            metadata: $metadata,
        );

        return $this->categoryRepository->save($category);
    }

    public function updateCategory(
        int $categoryId,
        ?string $name = null,
        ?string $slug = null,
        ?string $description = null,
        ?int $parentId = null,
        ?int $sortOrder = null,
        ?string $icon = null,
        ?string $image = null,
        ?array $metadata = null,
    ): Category {
        $category = $this->getCategoryById($categoryId);

        if ($slug !== null && $slug !== $category->slug && $this->categoryRepository->existsBySlug($slug)) {
            throw new \InvalidArgumentException('Category with this slug already exists');
        }

        if ($parentId !== null && $parentId !== $category->parentId) {
            $parent = $this->categoryRepository->findById($parentId);
            if ($parent === null) {
                throw new \InvalidArgumentException('Parent category not found');
            }
            $category = $category->withParent($parentId);
        }

        if ($sortOrder !== null) {
            $category = $category->withSortOrder($sortOrder);
        }

        return $this->categoryRepository->save($category);
    }

    public function activateCategory(int $categoryId): Category
    {
        $category = $this->getCategoryById($categoryId);

        $activatedCategory = $category->activate();

        return $this->categoryRepository->save($activatedCategory);
    }

    public function deactivateCategory(int $categoryId): Category
    {
        $category = $this->getCategoryById($categoryId);

        $deactivatedCategory = $category->deactivate();

        return $this->categoryRepository->save($deactivatedCategory);
    }

    public function getCategoryById(int $id): Category
    {
        $category = $this->categoryRepository->findById($id);

        if ($category === null) {
            throw new \InvalidArgumentException('Category not found');
        }

        return $category;
    }

    public function getCategoryBySlug(string $slug): Category
    {
        $category = $this->categoryRepository->findBySlug($slug);

        if ($category === null) {
            throw new \InvalidArgumentException('Category not found');
        }

        return $category;
    }

    public function getRootCategories(array $filters = []): Collection
    {
        return $this->categoryRepository->findRootCategories($filters);
    }

    public function getChildCategories(int $parentId, array $filters = []): Collection
    {
        return $this->categoryRepository->findByParent($parentId, $filters);
    }

    public function getCategoryTree(?int $parentId = null): Collection
    {
        return $this->categoryRepository->getTree($parentId);
    }

    public function getActiveCategories(array $filters = [], int $limit = 100): Collection
    {
        return $this->categoryRepository->findActive($filters, $limit);
    }

    public function deleteCategory(int $id): bool
    {
        $category = $this->getCategoryById($id);

        if ($this->categoryRepository->hasChildren($id)) {
            throw new \InvalidArgumentException('Cannot delete category with children');
        }

        return $this->categoryRepository->delete($id);
    }

    public function moveCategory(int $categoryId, ?int $newParentId): Category
    {
        $category = $this->getCategoryById($categoryId);

        if ($newParentId !== null) {
            $parent = $this->categoryRepository->findById($newParentId);
            if ($parent === null) {
                throw new \InvalidArgumentException('Parent category not found');
            }

            if ($newParentId === $categoryId) {
                throw new \InvalidArgumentException('Category cannot be its own parent');
            }
        }

        $movedCategory = $category->withParent($newParentId);

        return $this->categoryRepository->save($movedCategory);
    }
}
