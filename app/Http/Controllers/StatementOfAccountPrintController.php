<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\StatementOfAccountService;
use Illuminate\Http\Request;

class StatementOfAccountPrintController extends Controller
{
    public function print(Request $request)
    {
        $customer = Customer::findOrFail($request->query('customer_id'));

        $from = $request->query('from');
        $until = $request->query('until');

        $result = StatementOfAccountService::build($customer, $from, $until);

        return view('prints.statement-of-account', [
            'customer' => $customer,
            'entries' => $result['entries'],
            'openingBalance' => $result['openingBalance'],
            'closingBalance' => $result['closingBalance'],
            'from' => $from,
            'until' => $until,
            'generatedAt' => now(),
        ]);
    }
}