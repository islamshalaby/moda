<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;

class Shop  extends  Authenticatable
{
    protected $fillable = [
        'name',
        'logo',
        'cover',
        'email',
        'password',
        'seller_id',
        'status'    // 1 => active
    ];

    protected $appends = ['custom'];
    public function getCustomAttribute()
    {
        $data['setting'] = Setting::where('id' ,1)->select('app_name_en' , 'app_name_ar' , 'logo')->first();
        return $data;
    }

    public function products() {
        return $this->hasMany('App\Product', 'store_id');
    }

    public function categories() {
        return $this->belongsToMany('App\Category', 'stores_categories', 'store_id', 'category_id');
    }

    public function seller() {
        return $this->belongsTo('App\Seller', 'seller_id');
    }
}