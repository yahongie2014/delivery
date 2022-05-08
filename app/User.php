<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens , Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','address','country_id','phone' ,'language_id' , 'city_id'
    ];



    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];



    public function admin()
    {
        return $this->hasOne('App\Admin');
    }

    public function delivery()
    {
        return $this->hasOne('App\Delivery');
    }

    public function provider()
    {
        return $this->hasOne('App\Provider');
    }

    public function country()
    {
        return $this->belongsTo('App\Country');
    }

    public function city()
    {
        return $this->belongsTo('App\City');
    }

    public function language()
    {
        return $this->belongsTo('App\Language');
    }

    public function images(){
        return $this->hasMany('App\Image');
    }

    public function firebase_tokens()
    {
        return $this->hasMany('App\UserFireBaseToken');
    }
}
