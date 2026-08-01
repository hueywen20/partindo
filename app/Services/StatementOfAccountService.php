<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SalesReturn;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StatementOfAccountService
{
    /**
     * Build a chronological ledger of a customer's credit sales (debits),
     * payments, and approved returns (both credits), with a running balance.
     *
     * @return array{openingBalance: float, entries: Collection, closingBalance: float}
     */
    public static function build(Customer $customer, ?string $from = null, ?string $until = null): array
    {
        $salesQuery = $customer->sales()->where('payment_type', 'credit');
        $paymentsQuery = $customer->payments();
        $returnsQuery = $customer->salesReturns()->where('status', 'approved');

        $openingBalance = 0.0;
        if ($from) {
            $openingDebits = (clone $salesQuery)->where('date', '<', $from)->sum('final_total');
            $openingCredits = (clone $paymentsQuery)->where('date', '<', $from)->sum('amount');
            $openingReturnCredits = (clone $returnsQuery)->where('date', '<', $from)->sum('final_total');
            $openingBalance = (float) $openingDebits - (float) $openingCredits - (float) $openingReturnCredits;
        }

        $sales = (clone $salesQuery)
            ->when($from, fn ($q) => $q->where('date', '>=', $from))
            ->when($until, fn ($q) => $q->where('date', '<=', $until))
            ->get()
            ->map(fn (Sale $sale) => [
                'date' => Carbon::parse($sale->date)->format('Y-m-d'),
                'type' => 'Invoice',
                'reference' => $sale->sale_inv_no,
                'debit' => (float) $sale->final_total,
                'credit' => 0.0,
                'sort' => 0, // invoices sort before payments/returns on the same date
            ]);

        $payments = (clone $paymentsQuery)
            ->when($from, fn ($q) => $q->where('date', '>=', $from))
            ->when($until, fn ($q) => $q->where('date', '<=', $until))
            ->get()
            ->map(fn (Payment $payment) => [
                'date' => $payment->date->format('Y-m-d'),
                'type' => 'Payment',
                'reference' => $payment->reference_no ?: ucfirst(str_replace('_', ' ', $payment->method)),
                'debit' => 0.0,
                'credit' => (float) $payment->amount,
                'sort' => 1,
            ]);

        $returns = (clone $returnsQuery)
            ->when($from, fn ($q) => $q->where('date', '>=', $from))
            ->when($until, fn ($q) => $q->where('date', '<=', $until))
            ->get()
            ->map(fn (SalesReturn $return) => [
                'date' => Carbon::parse($return->date)->format('Y-m-d'),
                'type' => 'Return',
                'reference' => $return->return_no,
                'debit' => 0.0,
                'credit' => (float) $return->final_total,
                'sort' => 1,
            ]);

        $entries = $sales->concat($payments)->concat($returns)
            ->sortBy([['date', 'asc'], ['sort', 'asc']])
            ->values();

        $running = $openingBalance;
        $entries = $entries->map(function (array $entry) use (&$running) {
            $running += $entry['debit'] - $entry['credit'];
            $entry['balance'] = $running;

            return $entry;
        });

        return [
            'openingBalance' => $openingBalance,
            'entries' => $entries,
            'closingBalance' => $running,
        ];
    }
}