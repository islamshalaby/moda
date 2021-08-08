<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    protected $fillable = [
        'name',
        'shop',
        'phone',
        'email',
        'id_number',
        'instagram',
        'bank_name',
        'account_number',
        'front_image',
        'back_image',
        'details',
        'seen'
    ];
}