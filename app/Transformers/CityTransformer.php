<?php
namespace App\Transformers;

use App\City;
use League\Fractal\TransformerAbstract;

class CityTransformer extends TransformerAbstract
{


    public function transform(City $city)
    {
        return [
            'id' => $city->id,
            'name' => $city->language->first() ? $city->language->first()->name : $city->name,
            'country' => $city->country->name,
        ];
    }


}
?>