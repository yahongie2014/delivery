<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
    protected $table = 'orders';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'provider_id', 'delivery_id' , 'assigned_at' , 'delivered_at' , 'required_at' , 'client_name' , 'client_phone' , 'client_address' , 'order_lat' , 'order_long' , 'status' ,'details' , 'comments' , 'd_order_lat' , 'd_order_long' , 'price' ,'paid' , 'category_id' , 'main_service_type_id' , 'extra_service_type_id' , 'payment_type_id' , 'city_id'
    ];

    /**
     * Scope a query to only include users of a given type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $providerId
     * @param mixed $deliveryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $providerId = null , $deliveryId = null)
    {
        if($providerId)
            return $query->where('provider_id', $providerId);

        if($deliveryId)
            return $query->where('delivery_id', $deliveryId);
    }

    public function provider() {
        return $this->belongsTo('App\Provider');
    }

    public function delivery() {
        return $this->belongsTo('App\Delivery');
    }

    public function city() {
        return $this->belongsTo('App\City');
    }

    public function category() {
        return $this->belongsTo('App\Category');
    }

    public function main_service_type() {
        return $this->belongsTo('App\ServiceType');
    }

    public function extra_service_type() {
        return $this->belongsToMany('App\ServiceType','order_extra_services' ,'order_id' , 'service_type_id')->withPivot(['price','discount']);
    }

    public function payment_type() {
        return $this->belongsTo('App\PaymentType');
    }



}
