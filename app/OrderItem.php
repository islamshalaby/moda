<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_id', 'option_id', 'option_en', 'option_ar', 'val_en', 'val_ar', 'count', 'price_before_offer', 'final_price',
    'status'    // 1 => in progress
                // 2 => delivered
                // 3 => canceled
                // 4 => retrieved
    ];

    public function product()
    {
        return $this->belongsTo('App\Product', 'product_id');
    }

    public function product_with_select()
    {
        return $this->belongsTo('App\Product', 'product_id')->select('title_ar as product_name', 'type', 'final_price', 'price_before_offer', 'id');
    }

    public function order()
    {
        return $this->belongsTo('App\Order', 'order_id');
    }

    public function multiOption()
    {
        return $this->belongsTo('App\ProductMultiOption', 'option_id');
    }

    public function size() {
        return $this->hasOne('App\SizeDetail', 'order_id');
    }
}
