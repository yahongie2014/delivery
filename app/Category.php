<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    //
    protected $table = 'categories';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'status'
    ];

    public function orders(){
        return $this->hasMany('App\Order');
    }

    public function language(){
        return $this->belongsToMany('App\Language', 'category_language', 'category_id', 'language_id')->withPivot(['name']);
    }
}
