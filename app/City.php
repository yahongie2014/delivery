<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    //
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'status' , 'country_id'
    ];

    // get users Located in this city
    public function users(){
        return $this->hasMany('App\User');
    }

    //get citty name translations
    public function language(){
        return $this->belongsToMany('App\Language', 'city_language', 'city_id', 'language_id')->withPivot('name');
    }

    // get orders Located in this city
    public function orders(){
        return $this->hasMany('App\Order');
    }

    // get city country
    public function country() {
        return $this->belongsTo('App\Country');
    }
}
