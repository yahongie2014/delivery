<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    //
    protected $table = 'service_types';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'status' , 'type'
    ];

    public function orders_main(){
        return $this->hasMany('App\Order' , 'main_service_type_id' , 'id');
    }

    public function orders_extra(){
        return $this->hasMany('App\Order' , 'extra_service_type_id' , 'id');
    }

    public function country()
    {
        return $this->belongsToMany('App\Country' , 'services_types_price' ,'service_type_id' , 'country_id')->withPivot(['price']);
    }

    public function provider_discount()
    {
        return $this->belongsToMany('App\Provider' , 'provider_service_discount' ,'service_id' , 'provider_id')->withPivot(['discount']);
    }

    public function language(){
        return $this->belongsToMany('App\Language', 'service_type_language', 'service_type_id', 'language_id')->withPivot(['name']);
    }


}
