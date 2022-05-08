<?php
namespace App\Transformers;

use App\ServiceType;
use League\Fractal\TransformerAbstract;

class ServiceTransformer extends TransformerAbstract
{


    public function transform(ServiceType $service)
    {
        return [
            'id' => $service->id,
            'name' => $service->language->first() ? $service->language->first()->name : $service->name,
            'price' => $service->country->first() ? $service->country->first()->price : 0,
            'type' => $service->type == MAIN_SERVICE_TYPE ? "Main service" : "Extra service"
        ];
    }


}
?>