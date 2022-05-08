<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    //
    protected $table = 'providers';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'user_id', 'status'
    ];

    public function user() {
        return $this->belongsTo('App\User');
    }

    public function orders()
    {
        return $this->hasMany('App\Order');
    }

    public function services_discounts(){
        return $this->belongsToMany('App\ServiceType' , 'provider_service_discount' ,'provider_id' ,'service_id')->withPivot(['discount']);
    }

    public function payment_type_discounts(){
        return $this->belongsToMany('App\PaymentType' , 'provider_payment_discount' ,'provider_id' ,'payment_type_id')->withPivot(['discount']);
    }
}
