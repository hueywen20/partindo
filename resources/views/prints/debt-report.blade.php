<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Debt / AR Aging Report</title>
    <style>
        @page {
            size: portrait;
            margin: 14mm 12mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        .report-container {
            width: 100%;
            max-width: 185mm;
            margin: 0 auto;
        }

        .report-header {
            text-align: center;
            margin-bottom: 14px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }

        .report-header .company-name {
            font-size: 16px;
            font-weight: bold;
        }

        .report-header .report-title {
            font-size: 13px;
            font-weight: bold;
            margin-top: 4px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 10.5px;
        }

        .meta-table td {
            padding: 1px 0;
        }

        .grid-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-bottom: 10px;
        }

        .grid-table th,
        .grid-table td {
            border: 1px solid #000000;
            padding: 4px 6px;
        }

        .grid-table th {
            font-weight: bold;
            text-align: center;
            background-color: #f0f0f0;
        }

        .grid-table td.right { text-align: right; }
        .grid-table td.center { text-align: center; }

        .badge {
            display: inline-block;
            padding: 1px 6px;
            border: 1px solid #000;
            border-radius: 3px;
            font-size: 9.5px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-top: 4px;
        }

        .summary-table td {
            padding: 4px 0;
        }

        .summary-table .total-row td {
            font-weight: bold;
            font-size: 13px;
            border-top: 2px solid #000;
            padding-top: 6px;
        }

        .footer-note {
            margin-top: 20px;
            font-size: 9.5px;
            color: #333;
            text-align: right;
        }
    </style>
</head>
<body onload="window.print()">

<div class="report-container">

    <div class="report-header">
        <div class="company-name">{{ config('app.name') }}</div>
        <div class="report-title">Debt / Accounts Receivable Aging Report</div>
    </div>

    <table class="meta-table">
        <tr>
            <td><strong>Generated:</strong> {{ $generatedAt->format('d-m-Y H:i:s') }}</td>
            <td style="text-align: right;">
                @if($customer)
                    <strong>Customer:</strong> {{ $customer->company_name ?: $customer->customer_name }}
                @else
                    <strong>Customer:</strong> All Customers
                @endif
            </td>
        </tr>
        @if($bucketLabel)
            <tr>
                <td colspan="2"><strong>Aging Bucket:</strong> {{ $bucketLabel }}</td>
            </tr>
        @endif
    </table>

    <table class="grid-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 20%;">Customer</th>
                <th style="width: 14%;">Invoice No</th>
                <th style="width: 11%;">Date</th>
                <th style="width: 11%;">Due Date</th>
                <th style="width: 14%;">Balance</th>
                <th style="width: 11%;">Days Overdue</th>
                <th style="width: 14%;">Aging</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $index => $sale)
                @php
                    $dueDate = $sale->due_date;
                    $bucket = \App\Filament\Admin\Pages\Reports\DebtReport::agingBucket($dueDate);
                    $days = \App\Filament\Admin\Pages\Reports\DebtReport::daysOverdue($dueDate);
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $sale->customer?->customer_name ?? '—' }}</td>
                    <td>{{ $sale->sale_inv_no }}</td>
                    <td class="center">{{ \Illuminate\Support\Carbon::parse($sale->date)->format('d-m-Y') }}</td>
                    <td class="center">{{ $dueDate ? $dueDate->format('d-m-Y') : '—' }}</td>
                    <td class="right">{{ number_format($sale->balance, 2) }}</td>
                    <td class="center">{{ $days !== null && $days > 0 ? $days : '—' }}</td>
                    <td class="center">
                        <span class="badge">{{ \App\Filament\Admin\Pages\Reports\DebtReport::BUCKETS[$bucket] }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center">No outstanding credit invoices found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary-table">
        <tr class="total-row">
            <td style="text-align: left;">Total Outstanding</td>
            <td class="right" style="width: 40%;">{{ number_format($totalOutstanding, 2) }}</td>
        </tr>
    </table>

    <div class="footer-note">
        This report reflects outstanding credit sales as of {{ $generatedAt->format('d-m-Y H:i') }}.
    </div>

</div>

</body>
</html>