<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    //
    protected $table = 'languages';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'symbol' , 'direction' , 'status' , 'default'
    ];

    public function users() {
        return $this->hasMany('App\User');
    }

    public function country(){
        return $this->belongsToMany('App\Country', 'country_language',  'language_id' , 'country_id');
    }

    public function city(){
        return $this->belongsToMany('App\City', 'city_language',  'language_id' , 'city_id');
    }

    public function category(){
        return $this->belongsToMany('App\Category', 'category_language',  'language_id' , 'category_id');
    }

    public function payment_type(){
        return $this->belongsToMany('App\PaymentType', 'payment_type_language',  'language_id' , 'payment_type_id');
    }
}
