<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class SalesReturn extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'return_no',
        'date',
        'sale_id',
        'customer_id',
        'status',
        'reason',
        'notes',
        'tax',
        'discount',
        'grand_total',
        'final_total',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function items()
    {
        return $this->hasMany(SalesReturnItem::class);
    }

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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ─── Status helpers ───────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    // ─── Totals ───────────────────────────────────────────────────────────────

    public function recalculateTotals(): void
    {
        $this->loadMissing('items');
        $subtotal = $this->items->sum(fn ($item) => $item->qty * $item->price);
        $tax      = $subtotal * (($this->tax ?? 0) / 100);
        $final    = max(0, $subtotal + $tax - ($this->discount ?? 0));

        $this->updateQuietly([
            'grand_total' => $subtotal,
            'final_total' => $final,
        ]);
    }
}