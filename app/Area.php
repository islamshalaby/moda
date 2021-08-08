<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = ['title_en', 'title_ar', 'delivery_cost', 'deleted'];

    public function stores() {
        return $this->belongsToMany('App\Shop', 'delivery_areas', 'area_id', 'store_id')->select("*");
    }
}
