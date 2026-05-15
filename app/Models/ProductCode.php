<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCode extends Model
{
    //
    protected $fillable = ['product_id', 'code', 'brand'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
