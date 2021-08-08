<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    protected $fillable = ['image', 'title_en', 'title_ar', 'deleted'];

    public function brands() {
        return $this->hasMany('App\Brand', 'category_id');
    }

    public function products() {
        return $this->hasMany('App\Product', 'category_id');
    }

    public function recent_products() {
        return $this->products()->where('created_at', ">", DB::raw('NOW() - INTERVAL 1 WEEK'));
    }

    public function options() {
        return $this->belongsToMany('App\Option', 'options_categories', 'category_id', 'option_id');
    }

    public function multiOptions() {
        return $this->belongsToMany('App\MultiOption', 'multi_options_categories', 'category_id', 'multi_option_id');
    }

    public function optionsWithValues() {
        return $this->options()->with('values');
    }

    public function multiOptionsWithValues() {
        return $this->multiOptions()->with('values');
    }

    public function subCategories() {
        return $this->hasMany('App\SubCategory', 'category_id');
    }
}
