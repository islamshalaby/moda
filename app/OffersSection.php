<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OffersSection extends Model
{
    protected $fillable = ['title_en', 'title_ar', 'icon', 'sort'];

    public function offers() {
        return $this->belongsToMany('App\Product', 'control_offers', 'offers_section_id','offer_id');
    }
}