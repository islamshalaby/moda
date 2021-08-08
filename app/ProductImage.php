<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = ['image', 'product_id'];
    protected $hidden = ['product_id', 'id', 'created_at', 'updated_at'];
}
