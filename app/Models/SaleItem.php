<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'part_no',
        'brand',
        'qty',
        'price',
        'cost_price',
        'total',
        'notes',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function components()
    {
        return $this->hasMany(SaleItemComponent::class);
    }

    public function returns()
    {
        return $this->hasMany(SalesReturnItem::class);
    }
    
}