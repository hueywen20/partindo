<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quotation;

class QuotationPrintController extends Controller
{
    /**
     * Handle the printing layout for a specific quotation.
     */
    public function print(Quotation $quotation)
    {
        // Eager load relationships to prevent N+1 performance bottlenecks
        $quotation->load(['customer', 'items.product.uomModel']); 

        // Renders the blade layout located at resources/views/prints/quotation.blade.php
        return view('prints.quotation', compact('quotation'));
    }

    public function printDotMatrix(Quotation $quotation)
    {
        // Eager load relationships to prevent N+1 performance bottlenecks
        $quotation->load(['customer', 'items.product.uomModel']); 

        // Renders the blade layout located at resources/views/prints/quotation-dot-matrix.blade.php
        return view('prints.quotation-dot-matrix', compact('quotation'));
    }
}