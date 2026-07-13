<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PurchaseReportPrintController extends Controller
{
    public function print(Request $request)
    {
        $query = Purchase::query()->with('supplier');

        if ($from = $request->query('from')) {
            $query->whereDate('date', '>=', $from);
        }

        if ($until = $request->query('until')) {
            $query->whereDate('date', '<=', $until);
        }

        $supplier = null;
        if ($supplierId = $request->query('supplier_id')) {
            $query->where('supplier_id', $supplierId);
            $supplier = Supplier::find($supplierId);
        }

        $purchases = $query->orderBy('date')->get();

        return view('prints.purchase-report', [
            'purchases' => $purchases,
            'from' => $from ?? null,
            'until' => $until ?? null,
            'supplier' => $supplier,
            'totalAmount' => $purchases->sum('final_total'),
            'generatedAt' => now(),
        ]);
    }
}