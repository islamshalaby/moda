<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 
        'address_id', 
        'payment_method', 
        'subtotal_price', 
        'delivery_cost', 
        'total_price', 
        'status',
        'order_number',
        'store_id',
        'from_deliver_date',
        'to_deliver_date',
        'main_id'
    ];

    protected $dates = ['from_deliver_date', 'to_deliver_date'];

    public function user() {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function address() {
        return $this->belongsTo('App\UserAddress', 'address_id');
    }

    public function main() {
        return $this->belongsTo('App\MainOrder', 'main_id');
    }

    public function items() {
        return $this->belongsToMany('App\Product', 'order_items', 'order_id', 'product_id')->select('*');
    }

    public function oItems() {
        return $this->hasMany('App\OrderItem', 'order_id');
    }

    public function store() {
        return $this->belongsTo('App\Shop', 'store_id');
    }
}
