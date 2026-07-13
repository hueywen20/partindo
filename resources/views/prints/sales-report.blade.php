<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
    <style>
        @page { size: portrait; margin: 14mm 12mm; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            font-size: 10.5px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        .report-container { width: 100%; max-width: 190mm; margin: 0 auto; }

        .report-header {
            text-align: center;
            margin-bottom: 14px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }

        .report-header .company-name { font-size: 16px; font-weight: bold; }
        .report-header .report-title { font-size: 13px; font-weight: bold; margin-top: 4px; }

        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10px; }
        .meta-table td { padding: 1px 0; }

        .grid-table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 10px; }
        .grid-table th, .grid-table td { border: 1px solid #000000; padding: 3px 5px; }
        .grid-table th { font-weight: bold; text-align: center; background-color: #f0f0f0; }
        .grid-table td.right { text-align: right; }
        .grid-table td.center { text-align: center; }

        .summary-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 4px; }
        .summary-table .total-row td {
            font-weight: bold;
            font-size: 13px;
            border-top: 2px solid #000;
            padding-top: 6px;
        }

        .footer-note { margin-top: 20px; font-size: 9.5px; color: #333; text-align: right; }
    </style>
</head>
<body onload="window.print()">

<div class="report-container">

    <div class="report-header">
        <div class="company-name">{{ config('app.name') }}</div>
        <div class="report-title">Sales Report</div>
    </div>

    <table class="meta-table">
        <tr>
            <td>
                <strong>Period:</strong>
                {{ $from ? \Illuminate\Support\Carbon::parse($from)->format('d-m-Y') : 'Beginning' }}
                &nbsp;to&nbsp;
                {{ $until ? \Illuminate\Support\Carbon::parse($until)->format('d-m-Y') : 'Present' }}
            </td>
            <td style="text-align: right;">
                <strong>Generated:</strong> {{ $generatedAt->format('d-m-Y H:i') }}
            </td>
        </tr>
        <tr>
            <td><strong>Customer:</strong> {{ $customer?->customer_name ?? 'All Customers' }}</td>
            <td style="text-align: right;">
                <strong>Type:</strong> {{ $paymentType ? ucfirst($paymentType) : 'All' }}
                &nbsp;&nbsp;
                <strong>Status:</strong> {{ $paymentStatus ? ucfirst($paymentStatus) : 'All' }}
            </td>
        </tr>
    </table>

    <table class="grid-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 13%;">Invoice No</th>
                <th style="width: 18%;">Customer</th>
                <th style="width: 12%;">Subtotal</th>
                <th style="width: 12%;">Total</th>
                <th style="width: 9%;">Type</th>
                <th style="width: 12%;">Balance</th>
                <th style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $index => $sale)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">{{ \Illuminate\Support\Carbon::parse($sale->date)->format('d-m-Y') }}</td>
                    <td>{{ $sale->sale_inv_no }}</td>
                    <td>{{ $sale->customer?->customer_name ?? '—' }}</td>
                    <td class="right">{{ number_format($sale->grand_total, 2) }}</td>
                    <td class="right">{{ number_format($sale->final_total, 2) }}</td>
                    <td class="center">{{ ucfirst($sale->payment_type) }}</td>
                    <td class="right">{{ number_format($sale->balance, 2) }}</td>
                    <td class="center">{{ ucfirst($sale->payment_status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="center">No sales found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary-table">
        <tr class="total-row">
            <td style="text-align: left;">Total ({{ $sales->count() }} invoices)</td>
            <td class="right" style="width: 40%;">{{ number_format($totalAmount, 2) }}</td>
        </tr>
    </table>

    <div class="footer-note">
        This report reflects data as of {{ $generatedAt->format('d-m-Y H:i') }}.
    </div>

</div>

</body>
</html>