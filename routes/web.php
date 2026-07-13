<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuotationPrintController;
use App\Http\Controllers\QuotationController;
use App\Models\Quotation;

Route::get('/', function () {
    return view('welcome');
});


Route::middleware(['auth'])->group(function () {
    // We pass the controller as a string array here to bypass the IDE indexer bug
    Route::get('/quotations/{quotation}/print', ['App\Http\Controllers\QuotationPrintController', 'print'])
        ->name('quotations.print');

    Route::get('/quotations/{quotation}/print-dot-matrix', ['App\Http\Controllers\QuotationPrintController', 'printDotMatrix'])
        ->name('quotations.print-dot-matrix');
    Route::get('/reports/debt/print', ['App\Http\Controllers\DebtReportPrintController', 'print'])
        ->name('reports.debt.print');

});