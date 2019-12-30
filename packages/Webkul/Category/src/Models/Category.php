<?php

namespace Webkul\Category\Models;

use Webkul\Core\Eloquent\TranslatableModel;
use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webkul\Category\Contracts\Category as CategoryContract;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Category\Repositories\CategoryRepository;

/**
 * Class Category
 *
 * @package Webkul\Category\Models
 *
 * @property-read string $url_path maintained by database triggers
 */
class Category extends TranslatableModel implements CategoryContract
{
    use NodeTrait;

    public $translatedAttributes = ['name', 'description', 'slug', 'url_path', 'meta_title', 'meta_description', 'meta_keywords'];

    protected $fillable = ['position', 'status', 'display_mode', 'parent_id'];

    protected $with = ['translations'];

    /**
     * Get image url for the category image.
     */
    public function image_url()
    {
        if (! $this->image)
            return;

        return Storage::url($this->image);
    }

    /**
     * Get image url for the category image.
     */
    public function getImageUrlAttribute()
    {
        return $this->image_url();
    }

     /**
     * The filterable attributes that belong to the category.
     */
    public function filterableAttributes()
    {
        return $this->belongsToMany(AttributeProxy::modelClass(), 'category_filterable_attributes')->with('options');
    }

    /**
     * Returns all categories within the category's path
     *
     * @return Category[]
     */
    public function getPathCategories(): array
    {
        $category = $this->findInTree();

        $categories = [$category];

        while (isset($category->parent)) {
            $category = $category->parent;
            $categories[] = $category;
        }

        array_pop($categories);
        return array_reverse($categories);
    }

    /**
     * Finds and returns the category within a nested category tree
     * will search in root category by default
     *
     * @param Category[] $categoryTree
     * @return Category
     */
    private function findInTree($categoryTree = null): Category
    {
        if (! $categoryTree) {
            $rootCategoryId = core()->getCurrentChannel()->root_category_id;
            $categoryTree = app(CategoryRepository::class)->getVisibleCategoryTree($rootCategoryId);
        }

        foreach ($categoryTree as $category) {
            if ($category->id === $this->id) {
                return $category;
            }
            return $this->findInTree($category->children);
        }
        throw new NotFoundHttpException('category not found in tree');
    }
}