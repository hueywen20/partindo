<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    //
    protected $fillable = ['quotation_id', 'product_id', 'qty', 'price', 'total', 'notes', 'part_no', 'brand'];

    public function quotation() { return $this->belongsTo(Quotation::class); }
    public function product()   { return $this->belongsTo(Product::class); }

    public function components() { return $this->hasMany(QuotationItemComponent::class); }

}

