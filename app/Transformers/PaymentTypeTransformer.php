<?php
namespace App\Transformers;

use App\PaymentType;
use League\Fractal\TransformerAbstract;

class PaymentTypeTransformer extends TransformerAbstract
{


    public function transform(PaymentType $paymentType)
    {
        return [
            'id' => $paymentType->id,
            'name' => $paymentType->language->first() ? $paymentType->language->first()->name : $paymentType->name,
            'price' => $paymentType->country->first() ? $paymentType->country->first()->price : 0,
        ];
    }


}
?>