<?php

namespace App\Http\Controllers;

use App\Filament\Admin\Pages\Reports\DebtReport;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Http\Request;

class DebtReportPrintController extends Controller
{
    public function print(Request $request)
    {
        $query = Sale::query()
            ->where('payment_type', 'credit')
            ->where('payment_status', '!=', 'paid')
            ->with('customer');

        $customer = null;
        if ($customerId = $request->query('customer_id')) {
            $query->where('customer_id', $customerId);
            $customer = Customer::find($customerId);
        }

        $sales = $query->get();

        $bucket = $request->query('bucket');
        if ($bucket) {
            $sales = $sales->filter(
                fn (Sale $sale) => DebtReport::agingBucket($sale->due_date) === $bucket
            );
        }

        $sales = $sales->sortBy('date')->values();

        return view('prints.debt-report', [
            'sales' => $sales,
            'customer' => $customer,
            'bucketLabel' => $bucket ? (DebtReport::BUCKETS[$bucket] ?? null) : null,
            'totalOutstanding' => $sales->sum(fn (Sale $sale) => $sale->balance),
            'generatedAt' => now(),
        ]);
    }
}