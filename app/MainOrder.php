<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MainOrder extends Model
{
    protected $fillable = [
        'user_id', 
        'address_id', 
        'payment_method', 
        'subtotal_price', 
        'delivery_cost', 
        'total_price', 
        'status',
        'main_order_number'
    ];

    public function orders() {
        return $this->hasMany('App\Order', 'main_id');
    }

    public function orders_with_select() {
        return $this->hasMany('App\Order', 'main_id')->select('id', 'subtotal_price', 'delivery_cost', 'total_price', 'order_number', 'store_id', 'main_id', 'from_deliver_date', 'to_deliver_date');
    }

    public function user() {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function address() {
        return $this->belongsTo('App\UserAddress', 'address_id');
    }
}