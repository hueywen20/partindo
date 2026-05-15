<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    //
    protected $fillable = ['supplier_name', 'company_name', 'address', 'phone_no', 'status'];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}