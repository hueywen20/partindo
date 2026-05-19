<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Supplier extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = ['supplier_name', 'company_name', 'address', 'phone_no', 'status'];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}