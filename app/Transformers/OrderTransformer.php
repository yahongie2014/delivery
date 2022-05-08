<?php
namespace App\Transformers;

use App\Order;
use League\Fractal\TransformerAbstract;

class OrderTransformer extends TransformerAbstract
{
    protected $availableIncludes = [];

    public function transform(Order $order)
    {
        $orderExtraServices = [];
        foreach($order->extra_service_type as $extraService){
            $orderExtraServices[] = [
                'service_id' => $extraService->id,
                'name' => $extraService->language->first() ? $extraService->language->first()->name : $extraService->name,
                'price' => $extraService->pivot->price,
                'discount' => $extraService->pivot->discount,
            ];
        }

        return [
            'id' => $order->id,
            'assigned_at' => $order->assigned_at,
            'delivered_at' => $order->delivered_at,
            'required_at' => $order->required_at,
            'loading_at' => $order->loading_at,
            'client_name' => $order->client_name,
            'client_phone' => $order->client_phone,
            'client_address' => $order->client_address,
            'order_lat' => $order->order_lat,
            'order_long' => $order->order_long,
            'status' => $order->status,
            'details' => $order->details,
            'comments' => $order->comments,
            'price' => $order->price,
            'main_service_type_cost' => $order->main_service_type_cost,
            'main_service_type_discount' => $order->main_service_type_discount,
            'extra_service_type_cost' => $order->extra_service_type_cost,
            'extra_services_type_discount' => $order->extra_services_type_discount,
            'payment_type_cost' => $order->payment_type_cost,
            'payment_type_discount' => $order->payment_type_discount,
            'total_cost' => $order->total_cost,
            'total_discount' => $order->total_discount,
            'user_verification' => $order->user_verification,
            'user_updated' => $order->user_updated,
            'provider' => [
                'provider_id' => $order->provider_id,
                'user_id' => $order->provider->user->id,
                'name' => $order->provider->user->name,
                'email' => $order->provider->user->email,
                'phone' => $order->provider->user->phone,
            ],
            'delivery' => $order->delivery_id ? [
                'delivery_id' => $order->delivery_id,
                'user_id' => $order->delivery->user->id,
                'name' => $order->delivery->user->name,
                'email' => $order->delivery->user->email,
                'phone' => $order->delivery->user->phone,
            ]: null ,
            'city' => [
                'city_id' => $order->city_id,
                'name' => $order->city->language->first() ? $order->city->language->first()->name : $order->city->name,

            ],
            'extra_service_type' => $orderExtraServices,
            'category' => [
                'category_id' => $order->category_id,
                'name' => $order->category->language->first() ? $order->category->language->first()->name : $order->category->name,
            ],
            'main_service_type' => [
                'service_id' => $order->main_service_type_id,
                'name' => $order->main_service_type->language->first() ? $order->main_service_type->language->first()->name : $order->main_service_type->name,
                'price' => $order->main_service_type_cost,
                'discount' => $order->main_service_type_discount,
            ],
            'payment_type' => [
                'payment_type_id' => $order->payment_type_id,
                'name' => $order->payment_type->language->first() ? $order->payment_type->language->first()->name : $order->payment_type->name,
                'price' => $order->payment_type_cost,
                'discount' => $order->payment_type_discount,
            ],
            'updated_at' => $order->updated_at
        ];
    }

    public function includeProvider(Order $order)
    {
        return $this->item($order->provider, \App::make(ProviderTransformer::class));
    }

    public function includeDelivery(Order $order)
    {
        return $this->item($order->delivery, \App::make(DeliveryTransformer::class));
    }


}
?>