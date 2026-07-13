<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Customer Report</title>
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
        <div class="report-title">Customer Report</div>
    </div>

    <table class="meta-table">
        <tr>
            <td>
                <strong>Filter:</strong> {{ $hasOutstandingOnly ? 'Customers with outstanding balance only' : 'All Customers' }}
            </td>
            <td style="text-align: right;">
                <strong>Generated:</strong> {{ $generatedAt->format('d-m-Y H:i') }}
            </td>
        </tr>
    </table>

    <table class="grid-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 20%;">Customer</th>
                <th style="width: 16%;">Company</th>
                <th style="width: 12%;">Phone</th>
                <th style="width: 8%;">Invoices</th>
                <th style="width: 14%;">Total Sales</th>
                <th style="width: 13%;">Total Paid</th>
                <th style="width: 13%;">Outstanding</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $index => $customer)
                @php
                    $outstanding = max(0, ($customer->credit_sales_amount ?? 0) - ($customer->total_paid_amount ?? 0));
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $customer->customer_name }}</td>
                    <td>{{ $customer->company_name ?? '—' }}</td>
                    <td>{{ $customer->phone_no ?? '—' }}</td>
                    <td class="center">{{ $customer->invoice_count }}</td>
                    <td class="right">{{ number_format($customer->total_sales_amount ?? 0, 2) }}</td>
                    <td class="right">{{ number_format($customer->total_paid_amount ?? 0, 2) }}</td>
                    <td class="right">{{ number_format($outstanding, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center">No customers found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary-table">
        <tr class="total-row">
            <td style="text-align: left;">Total Outstanding ({{ $customers->count() }} customers)</td>
            <td class="right" style="width: 40%;">{{ number_format($totalOutstanding, 2) }}</td>
        </tr>
    </table>

    <div class="footer-note">
        This report reflects data as of {{ $generatedAt->format('d-m-Y H:i') }}.
    </div>

</div>

</body>
</html>