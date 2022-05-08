<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    //
    protected $table = 'payment_types';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'status'
    ];

    public function orders(){
        return $this->hasMany('App\Order' , 'payment_type_id' , 'id');
    }

    public function country()
    {
        return $this->belongsToMany('App\Country' , 'payment_types_prices' ,'payment_type_id' , 'country_id')->withPivot(['price']);
    }

    public function language(){
        return $this->belongsToMany('App\Language', 'payment_type_language', 'payment_type_id', 'language_id')->withPivot(['name']);
    }

    public function provider_discount()
    {
        return $this->belongsToMany('App\Provider' , 'provider_payment_discount' ,'payment_type_id' , 'provider_id')->withPivot(['discount']);
    }
}
