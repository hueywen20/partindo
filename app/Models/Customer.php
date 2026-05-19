<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Customer extends Model implements Auditable
{
    //
    use AuditableTrait;

    protected $fillable = ['customer_name', 'company_name', 'phone_no', 'status'];
}
