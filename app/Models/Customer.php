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

    // ─── Relationships ────────────────────────────────────────────────────────

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function salesReturns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    // ─── Debt helpers ─────────────────────────────────────────────────────────

    /**
     * Total outstanding balance across all this customer's credit sales.
     */
    public function getOutstandingBalanceAttribute(): float
    {
        return (float) $this->sales()
            ->where('payment_type', 'credit')
            ->where('payment_status', '!=', 'paid')
            ->get()
            ->sum(fn (Sale $sale) => $sale->balance);
    }
}