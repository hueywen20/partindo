<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Uom extends Model implements Auditable   
{
    use AuditableTrait;

    protected $fillable = ['name', 'status', 'created_by'];

     protected static function booted(): void
    {
        static::creating(function ($uom) {
            $uom->created_by = Auth::user()?->name;
        });
    }

}
