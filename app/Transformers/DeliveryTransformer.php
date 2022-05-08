<?php
namespace App\Transformers;

use App\Delivery;
use League\Fractal\TransformerAbstract;

class DeliveryTransformer extends TransformerAbstract
{
    protected $availableIncludes = [
        'user'
    ];

    public function transform(Delivery $delivery)
    {
        return [
            'delivery_id' => $delivery->id,
        ];
    }

    public function includeUser(Delivery $delivery)
    {
        return $this->item($delivery->user, \App::make(UserTransformer::class));
    }
}
?>