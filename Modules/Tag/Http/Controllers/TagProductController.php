<?php

namespace Modules\Tag\Http\Controllers;

use Modules\Tag\Entities\Tag;
use Modules\Product\Entities\Product;
use Modules\Product\Filters\ProductFilter;
use Modules\Product\Http\Controllers\ProductSearch;

class TagProductController
{
    use ProductSearch;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($slug, Product $model, ProductFilter $productFilter)
    {
        request()->merge(['tag' => $slug]);

        if (request()->expectsJson()) {
            return $this->searchProducts($model, $productFilter);
        }

        return view('public.products.index', [
            'tagName' => Tag::findBySlug($slug)->name,
        ]);
    }
}
