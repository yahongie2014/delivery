<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    //
    protected $table = 'countries';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'status' , 'currency_name' ,'currency_symbol' , 'code' , 'flag' , 'phone'
    ];

    public function users(){
        return $this->hasMany('App\User');
    }

    public function services_types()
    {
        return $this->belongsToMany('App\ServiceType' , 'services_types_price' , 'country_id' ,'service_type_id' );
    }

    public function payments_types()
    {
        return $this->belongsToMany('App\PaymentType' , 'payment_types_prices' , 'country_id' ,'payment_type_id' );
    }

    public function language(){
        return $this->belongsToMany('App\Language', 'country_language', 'country_id', 'language_id')->withPivot('name');
    }

    public function scopeTranslation($query)
    {
        $countryTranslation = $query->language()->first();
        if($countryTranslation){
            $query->name = $countryTranslation->pivot()->name();
        }
        return $query;
    }

    public function getTranslatedAttribute()
    {
        // Get languages
        $languages = $this->language->where('id',$this->language_id);

        unset($this->language);
        foreach ($languages as $countryLanguage){
            if($countryLanguage->pivot->name)
                return $countryLanguage->pivot->name;
        }

        return $this->name;
    }
}
