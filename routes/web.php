<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuotationPrintController;
use App\Models\Quotation;

Route::get('/', function () {
    return view('welcome');
});


Route::middleware(['auth'])->group(function () {
    // We pass the controller as a string array here to bypass the IDE indexer bug
    Route::get('/quotations/{quotation}/print', ['App\Http\Controllers\QuotationPrintController', 'print'])
        ->name('quotations.print');
});