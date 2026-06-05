<style>
    /* 1. Sets the clean, sans-serif font for the entire document */
    body {
        font-family: Arial, Helvetica, sans-serif;
        color: #000000;
        font-size: 12px; /* Adjust if you need the text slightly larger or smaller */
    }

    /* 2. Styling for the top Date/Name/Unit section */
    .info-table {
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: bold; /* Matches the bold text in your screenshot */
    }
    
    .info-table td {
        padding: 4px 10px 4px 0;
        vertical-align: top;
    }

    /* 3. Grid Table Styling */
    .grid-table {
        width: 100%;
        border-collapse: collapse; /* Merges double lines into single sharp lines */
        font-size: 12px;
    }

    .grid-table th, 
    .grid-table td {
        border: 1px solid #000000 !important; /* Forces solid black borders */
        empty-cells: show !important;        /* Forces browser to render blank cell borders */
        padding: 6px;
    }

    /* 4. Adds the light grey background to the table headers */
    .grid-table th {
        background-color: #f2f2f2 !important; 
    }
</style>

<table class="info-table" style="border: none;">
    {{-- {{ dd($quotation->customer->toArray()) }} --}}
    <tr>
        <td style="width: 60px;">DATE</td>
        <td>: {{ \Carbon\Carbon::parse($quotation->date)->format('l, F j, Y') }}</td>    </tr>
    <tr>
        <td>NAME</td>
        <td>: {{ $quotation->customer->company_name ?: $quotation->customer->customer_name }}</td>
    </tr>
    <tr>
        <td>UNIT</td>
        <td>: {{ $quotation->excavator_model ?: '—' }}</td>
    </tr>
</table>

<table class="grid-table">
    <thead>
        <tr>
            <th style="width: 4%;" class="text-center">NO</th>
            <th style="width: 5%;" class="text-center">QTY</th>
            <th style="width: 6%;">UOM</th>
            <th style="width: 18%;">PART NO</th>
            <th style="width: 12%;">BRAND</th>
            <th style="width: 23%;">DESCRIPTION</th>
            <th style="width: 11%;" class="text-right">UNIT PRICE</th>
            <th style="width: 11%;" class="text-right">AMOUNT</th>
            <th style="width: 10%;">INVOICE NO</th>
        </tr>
    </thead>
    <tbody>
        @foreach($quotation->items as $index => $item)
            @php
                $cleanPartNo = str_contains($item->part_no, '::') 
                    ? explode('::', $item->part_no)[1] 
                    : $item->part_no;

                $qty = $item->qty ?? 0; 
                $unitPrice = $item->price ?? 0;
                $description = $item->product?->name ?? $item->product_name ?? '—';
                $amount = $qty * $unitPrice;
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ $qty }}</td>
                <td>{{ $item->product?->uomModel?->name }}</td> 
                <td>{{ $cleanPartNo }}</td>
                <td>{{ $item->brand ?? '—' }}</td>
                <td>{{ $description }}</td>
                <td class="text-right">Rp {{ number_format($unitPrice, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($amount, 0, ',', '.') }}</td>
                <td>—</td>
            </tr>
        @endforeach

        <tr>
            <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
            <td class="text-right" style="font-weight: bold; background-color: #f2f2f2;">Subtotal</td>
            <td class="text-right" style="font-weight: bold;">Rp {{ number_format($quotation->grand_total, 0, ',', '.') }}</td>
            <td>&nbsp;</td>
        </tr>

        @if(($quotation->tax) > 0)
            <tr>
                <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                <td class="text-right" style="font-weight: bold; background-color: #f2f2f2;">Tax ({{ $quotation->tax }}%)</td>
                <td class="text-right">Rp {{ number_format(($quotation->grand_total) * ($quotation->tax / 100), 0, ',', '.') }}</td>
                <td>&nbsp;</td>
            </tr>
        @endif

        @if(($quotation->discount) > 0)
            <tr>
                <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                <td class="text-right" style="font-weight: bold; background-color: #f2f2f2;">Discount</td>
                <td class="text-right" style="color: red;">- Rp {{ number_format($quotation->discount, 0, ',', '.') }}</td>
                <td>&nbsp;</td>
            </tr>
        @endif

        <tr>
            <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
            <td class="text-right" style="font-weight: bold; background-color: #e6e6e6;">Final Total</td>
            <td class="text-right" style="font-weight: bold; background-color: #f2f2f2;">
                Rp {{ number_format($quotation->final_total, 0, ',', '.') }}
            </td>
            <td>&nbsp;</td>
        </tr>
    </tbody>
</table>