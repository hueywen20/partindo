<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Payment extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'date',
        'amount',
        'method',
        'reference_no',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted()
    {
        static::saved(function (Payment $payment) {
            $payment->sale?->refreshPaymentStatus();
        });

        static::deleted(function (Payment $payment) {
            $payment->sale?->refreshPaymentStatus();
        });
    }
}