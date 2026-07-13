<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Http\Request;

class SalesReportPrintController extends Controller
{
    public function print(Request $request)
    {
        $query = Sale::query()->with('customer');

        if ($from = $request->query('from')) {
            $query->whereDate('date', '>=', $from);
        }

        if ($until = $request->query('until')) {
            $query->whereDate('date', '<=', $until);
        }

        $customer = null;
        if ($customerId = $request->query('customer_id')) {
            $query->where('customer_id', $customerId);
            $customer = Customer::find($customerId);
        }

        if ($paymentType = $request->query('payment_type')) {
            $query->where('payment_type', $paymentType);
        }

        if ($paymentStatus = $request->query('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        $sales = $query->orderBy('date')->get();

        return view('prints.sales-report', [
            'sales' => $sales,
            'from' => $from ?? null,
            'until' => $until ?? null,
            'customer' => $customer,
            'paymentType' => $paymentType ?? null,
            'paymentStatus' => $paymentStatus ?? null,
            'totalAmount' => $sales->sum('final_total'),
            'generatedAt' => now(),
        ]);
    }
}