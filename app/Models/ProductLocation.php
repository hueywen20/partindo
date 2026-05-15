<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProductLocation extends Model
{
    //
    protected $fillable = ['name', 'status', 'created_by'];

    protected static function booted(): void
    {
        static::creating(function ($brand) {
            $brand->created_by = Auth::user()?->name;
        });
    }
}
