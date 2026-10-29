<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    public function getAllWithChildren(): Collection
    {
        return Category::with(['children', 'attributes.options'])
            ->orderBy('sort_order')
            ->get();
    }

    public function findWithProducts(int $id): ?Category
    {
        return Category::with([
            'children',
            'attributes.options',
            'products' => function ($q) {
                $q->where('status', 'active');
            },
        ])->find($id);
    }

    public function getAttributes(int $categoryId): Collection
    {
        return CategoryAttribute::with('options')
            ->where('category_id', $categoryId)
            ->orderBy('sort_order')
            ->get();
    }

    public function getTree(): Collection
    {
        return Category::whereNull('parent_id')
            ->with([
                'children' => function ($q) {
                    $q->orderBy('sort_order');
                },
                'attributes.options'
            ])
            ->orderBy('sort_order')
            ->get();
    }

    public function getAll(): LengthAwarePaginator
    {
        return Category::with(['parent', 'attributes.options'])
            ->orderBy('sort_order')
            ->paginate(20);
    }

    public function findById(int $id): ?Category
    {
        return Category::with(['parent', 'attributes.options'])->find($id);
    }

    public function create(array $data): Category
    {
        $category = Category::create([
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'] ?? str()->slug($data['name']),
            'description' => $data['description'] ?? null,
            'image' => $data['image'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'status' => $data['status'] ?? 'approved',
            'created_by' => $data['created_by'] ?? null,
        ]);

        if (!empty($data['attributes'])) {
            $this->syncAttributes($category, $data['attributes']);
        }

        return $category;
    }

    public function update(int $id, array $data): ?Category
    {
        $category = Category::find($id);
        if (!$category) return null;

        $category->update([
            'parent_id' => $data['parent_id'] ?? $category->parent_id,
            'name' => $data['name'] ?? $category->name,
            'slug' => $data['slug'] ?? $category->slug,
            'description' => $data['description'] ?? $category->description,
            'image' => $data['image'] ?? $category->image,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
            'is_active' => $data['is_active'] ?? $category->is_active,
        ]);

        if (array_key_exists('attributes', $data)) {
            $this->syncAttributes($category, $data['attributes']);
        }

        return $category->fresh();
    }

    protected function syncAttributes(Category $category, array $attributes): void
    {
        // Remove attributes not present in the request
        $attributeIds = array_filter(array_column($attributes, 'id'));
        $category->attributes()->whereNotIn('id', $attributeIds)->delete();

        foreach ($attributes as $attrData) {
            $attribute = $category->attributes()->updateOrCreate(
                ['id' => $attrData['id'] ?? null],
                [
                    'name' => $attrData['name'],
                    'type' => $attrData['type'] ?? 'select',
                    'is_required' => $attrData['is_required'] ?? true,
                    'sort_order' => $attrData['sort_order'] ?? 0,
                ]
            );

            // Sync options for this attribute
            if (isset($attrData['options'])) {
                $optionIds = array_filter(array_column($attrData['options'], 'id'));
                $attribute->options()->whereNotIn('id', $optionIds)->delete();

                foreach ($attrData['options'] as $optData) {
                    $attribute->options()->updateOrCreate(
                        ['id' => $optData['id'] ?? null],
                        [
                            'value' => $optData['value'],
                            'sort_order' => $optData['sort_order'] ?? 0,
                        ]
                    );
                }
            }
        }
    }

    public function delete(int $id): bool
    {
        $category = Category::find($id);
        if (!$category) return false;

        Category::where('parent_id', $id)->update(['parent_id' => null]);

        return $category->delete();
    }
}
