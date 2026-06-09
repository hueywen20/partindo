<style>
    /* ── Page & Print Setup ── */
    @page {
        size: portrait;
        margin: 10mm 12mm; 
    }

    body {
        font-family: Arial, Helvetica, sans-serif;
        color: #000000;
        font-size: 11px;
        line-height: 1.3;
        margin: 0;
        padding: 0;
        background-color: #ffffff;
    }

    /* Constrains width on screen and maps perfectly to standard portrait print width */
    .invoice-container {
        width: 100%;
        max-width: 185mm; 
        margin: 0;
        box-sizing: border-box;
    }

    /* ── Structural Layout Tables ── */
    .layout-table {
        width: 100%;
        border-collapse: collapse;
        border: none;
        margin-bottom: 8px;
    }

    .layout-table td {
        padding: 0;
        vertical-align: top;
        border: none;
    }

    /* ── Fully Boxed Grid Items Table ── */
    .grid-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        margin-top: 4px;
        margin-bottom: 12px;
        /* border: 1px solid #000000; Outer frame border */
    }

    .grid-table th,
    .grid-table td {
        empty-cells: show;
        padding: 4px 6px;          /* Tight padding for a compact vertical look */
        /* border: 1px solid #000000; Sharp internal grid lines */
    }

    .grid-table th {
        font-weight: bold;
        text-align: center;
        background-color: transparent;
    }

    .grid-table td.center { text-align: center; }
    .grid-table td.right  { text-align: right; }

    /* Compact height for empty grid lines */
    .grid-table tr.filler td { 
        height: 18px; 
    }

    /* ── Footer Elements ── */
    .inner-sig-table {
        width: 100%;
        border-collapse: collapse;
        text-align: center;
    }

    .inner-sig-table td {
        padding: 0;
        font-weight: bold;
        font-size: 11px;
    }

    .sig-line {
        margin-top: 40px;
        border-top: 1px solid #000;
        width: 75%;
        margin-left: auto;
        margin-right: auto;
    }

    .footer-notice {
        font-size: 9.5px;
        font-weight: bold;
        margin-top: 10px;
    }

    .summary-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }

    .summary-table td {
        padding: 2px 4px;
    }

    .summary-table .total-row td {
        font-weight: bold;
        font-size: 12px;
        border-top: 1px solid #000;
        padding-top: 4px;
    }

    .page-info {
        text-align: right;
        font-size: 10px;
        margin-top: 4px;
    }
</style>

@php
    $items     = $quotation->items;
    $itemCount = $items->count();
    
    // Kept at 13 total lines to maintain the standard half-page data grid depth
    $minRows   = 13; 
    $fillerRows = max(0, $minRows - $itemCount);

    $subtotal   = $quotation->grand_total;
    $taxAmount  = $quotation->tax > 0 ? $subtotal * ($quotation->tax / 100) : 0;
    $discount   = $quotation->discount ?? 0;
    $finalTotal = $quotation->final_total;
@endphp

<div class="invoice-container">

    {{-- ── HEADER METADATA ── --}}
    <table class="layout-table">
        <tr>
            <td style="width: 50%;">
                <div style="font-size: 12px; font-weight: bold; margin-bottom: 2px;">NO : {{ $quotation->quotation_no }}</div>
                @if($quotation->excavator_model)
                    <div style="font-size: 11px; font-weight: bold;">UNIT : {{ $quotation->excavator_model }}</div>
                @endif
            </td>
            <td style="text-align: right; width: 50%; font-size: 11px;">
                Tanggal : {{ \Carbon\Carbon::parse($quotation->date)->format('d-M-Y') }}<br>
                <strong>Kepada : {{ $quotation->customer->company_name ?: $quotation->customer->customer_name }}</strong><br>
                <div style="margin-top: 2px;">Jatuh Tempo : {{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('d-M-Y') : '—' }}</div>
            </td>
        </tr>
    </table>

    {{-- ── MAIN ITEM GRID ── --}}
    <table class="grid-table">
        <thead>
            <tr>
                <th style="width: 5%;">NO</th>
                <th style="width: 10%;">QTY</th>
                <th style="width: 8%;">UOM</th>
                <th style="width: 20%;">NO PART</th>
                <th style="width: 32%;">NAMA BARANG</th>
                <th style="width: 11%;">@ HARGA</th>
                <th style="width: 14%;">SUB TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
                @php
                    $cleanPartNo = str_contains($item->part_no, '::')
                        ? explode('::', $item->part_no)[1]
                        : $item->part_no;
                    $qty         = $item->qty ?? 0;
                    $unitPrice   = $item->price ?? 0;
                    $description = $item->product?->name ?? $item->product_name ?? '—';
                    $uom         = $item->product?->uomModel?->name ?? 'PCS';
                    $amount      = $qty * $unitPrice;
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">{{ number_format($qty, 2, ',', '.') }}</td>
                    <td class="center">{{ $uom }}</td>
                    <td>{{ $cleanPartNo }}</td>
                    <td>{{ $description }}</td>
                    <td class="right">{{ number_format($unitPrice, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach

            {{-- Empty Grid Box Placeholder Rows ── --}}
            @for($i = 0; $i < $fillerRows; $i++)
                <tr class="filler">
                    <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                    <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{-- ── FOOTER ACTIONS & TOTALS ── --}}
    <table class="layout-table" style="margin-top: 5px;">
        <tr>
            <td style="width: 58%;">
                <table class="inner-sig-table">
                    <tr>
                        <td style="width: 50%;">DIPERIKSA OLEH</td>
                        <td style="width: 50%;">TANDA TERIMA</td>
                    </tr>
                    <tr>
                        <td><div class="sig-line"></div></td>
                        <td><div class="sig-line"></div></td>
                    </tr>
                </table>
                <div class="footer-notice">Perhatian ! Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</div>
            </td>

            <td style="width: 6%;"></td>

            <td style="width: 36%;">
                <table class="summary-table">
                    <tr>
                        <td style="text-align: left; width: 35%;"><strong>PPN</strong></td>
                        <td style="text-align: left; width: 15%;">: Rp.</td>
                        <td class="right" style="width: 50%;">{{ $taxAmount > 0 ? number_format($taxAmount, 2, ',', '.') : '0,00' }}</td>
                    </tr>
                    <tr class="total-row">
                        <td style="text-align: left;"><strong>TOTAL</strong></td>
                        <td style="text-align: left;">: Rp.</td>
                        <td class="right"><strong>{{ number_format($finalTotal, 0, ',', '.') }}</strong></td>
                    </tr>
                </table>
                <div class="page-info">Hal 1 / 1</div>
            </td>
        </tr>
    </table>

</div>