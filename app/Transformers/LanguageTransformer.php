<?php
namespace App\Transformers;

use App\Language;
use League\Fractal\TransformerAbstract;

class LanguageTransformer extends TransformerAbstract
{


    public function transform(Language $language)
    {
        return [
            'id' => $language->id,
            'name' => $language->name,
        ];
    }


}
?>