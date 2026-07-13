<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Statement of Account - {{ $customer->customer_name }}</title>
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
            vertical-align: top;
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

        .grid-table tr.summary-row td {
            font-weight: bold;
            background-color: #f7f7f7;
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
        <div class="report-title">Statement of Account</div>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 55%;">
                <strong>{{ $customer->company_name ?: $customer->customer_name }}</strong><br>
                @if($customer->company_name)
                    {{ $customer->customer_name }}<br>
                @endif
                @if($customer->phone_no)
                    Tel: {{ $customer->phone_no }}
                @endif
            </td>
            <td style="width: 45%; text-align: right;">
                <strong>Period:</strong>
                {{ $from ? \Illuminate\Support\Carbon::parse($from)->format('d-m-Y') : 'Beginning' }}
                &nbsp;to&nbsp;
                {{ $until ? \Illuminate\Support\Carbon::parse($until)->format('d-m-Y') : 'Present' }}
                <br>
                <strong>Generated:</strong> {{ $generatedAt->format('d-m-Y H:i') }}
            </td>
        </tr>
    </table>

    <table class="grid-table">
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 13%;">Type</th>
                <th style="width: 25%;">Reference</th>
                <th style="width: 16%;">Debit</th>
                <th style="width: 16%;">Credit</th>
                <th style="width: 18%;">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr class="summary-row">
                <td colspan="5">Opening Balance</td>
                <td class="right">{{ number_format($openingBalance, 2) }}</td>
            </tr>

            @forelse($entries as $entry)
                <tr>
                    <td class="center">{{ \Illuminate\Support\Carbon::parse($entry['date'])->format('d-m-Y') }}</td>
                    <td class="center">{{ $entry['type'] }}</td>
                    <td>{{ $entry['reference'] }}</td>
                    <td class="right">{{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '—' }}</td>
                    <td class="right">{{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '—' }}</td>
                    <td class="right">{{ number_format($entry['balance'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center">No activity in this period.</td>
                </tr>
            @endforelse

            <tr class="summary-row">
                <td colspan="5">Closing Balance</td>
                <td class="right">{{ number_format($closingBalance, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="summary-table">
        <tr class="total-row">
            <td style="text-align: left;">Amount Due</td>
            <td class="right" style="width: 40%;">{{ number_format(max(0, $closingBalance), 2) }}</td>
        </tr>
    </table>

    <div class="footer-note">
        Please settle outstanding balance at your earliest convenience. This is a computer-generated statement.
    </div>

</div>

</body>
</html>