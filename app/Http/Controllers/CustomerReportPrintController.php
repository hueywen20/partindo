<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CustomerReportPrintController extends Controller
{
    public function print(Request $request)
    {
        $query = Customer::query()
            ->withCount('sales as invoice_count')
            ->withSum('sales as total_sales_amount', 'final_total')
            ->withSum(['sales as credit_sales_amount' => fn (Builder $q) => $q->where('payment_type', 'credit')], 'final_total')
            ->withSum('payments as total_paid_amount', 'amount');

        if ($request->boolean('has_outstanding')) {
            $query->whereHas(
                'sales',
                fn (Builder $q) => $q->where('payment_type', 'credit')->where('payment_status', '!=', 'paid')
            );
        }

        $customers = $query->orderBy('customer_name')->get();

        $totalOutstanding = $customers->sum(
            fn (Customer $c) => max(0, ($c->credit_sales_amount ?? 0) - ($c->total_paid_amount ?? 0))
        );

        return view('prints.customer-report', [
            'customers' => $customers,
            'hasOutstandingOnly' => $request->boolean('has_outstanding'),
            'totalOutstanding' => $totalOutstanding,
            'generatedAt' => now(),
        ]);
    }
}