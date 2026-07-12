<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Sale extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = [
        'date',
        'sale_inv_no',
        'customer_id',
        'quotation_id',
        'purchase_order_id',
        'tax',
        'discount',
        'grand_total',
        'final_total',
        'payment_type',
        'payment_terms_days',
        'payment_status',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // ─── Payment status ───────────────────────────────────────────────────────

    public function isCredit(): bool
    {
        return $this->payment_type === 'credit';
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getBalanceAttribute(): float
    {
        return max(0, round((float) $this->final_total - $this->paid_amount, 2));
    }

    public function getDueDateAttribute(): ?\Illuminate\Support\Carbon
    {
        if (! $this->isCredit() || ! $this->payment_terms_days) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($this->date)->addDays($this->payment_terms_days);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->isCredit()
            && $this->balance > 0
            && $this->due_date !== null
            && $this->due_date->isPast();
    }

    /**
     * Recompute and persist payment_status based on current payments.
     * Called whenever a Payment is saved/deleted, and after totals change.
     */
    public function refreshPaymentStatus(): void
    {
        if (! $this->isCredit()) {
            $status = 'paid';
        } else {
            $paid = $this->paid_amount;
            $status = match (true) {
                $paid <= 0 => 'unpaid',
                $paid < (float) $this->final_total => 'partial',
                default => 'paid',
            };
        }

        if ($status !== $this->payment_status) {
            $this->updateQuietly(['payment_status' => $status]);
        }
    }

    // ─── Recalculate totals ───────────────────────────────────────────────────

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

        $this->refreshPaymentStatus();
    }
};