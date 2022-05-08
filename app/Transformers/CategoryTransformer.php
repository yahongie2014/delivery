<?php
namespace App\Transformers;

use App\Category;
use League\Fractal\TransformerAbstract;

class CategoryTransformer extends TransformerAbstract
{


    public function transform(Category $category)
    {
        return [
            'id' => $category->id,
            'name' => $category->language->first() ? $category->language->first()->name : $category->name,
        ];
    }


}
?>