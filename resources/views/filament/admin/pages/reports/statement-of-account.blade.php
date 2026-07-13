<x-filament-panels::page>
    <form wire:submit="generate">
        {{ $this->form }}

        <div style="margin-top: 1rem;">
            <x-filament::button type="submit">
                Generate Statement
            </x-filament::button>
        </div>
    </form>

    @if($generated && $customer)
        <div style="margin-top: 2rem;">
            <div style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">
                {{ $customer->company_name ?: $customer->customer_name }}
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <th style="text-align: left; padding: 8px 12px;">Date</th>
                            <th style="text-align: left; padding: 8px 12px;">Type</th>
                            <th style="text-align: left; padding: 8px 12px;">Reference</th>
                            <th style="text-align: right; padding: 8px 12px;">Debit</th>
                            <th style="text-align: right; padding: 8px 12px;">Credit</th>
                            <th style="text-align: right; padding: 8px 12px;">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid #e5e7eb; background-color: rgba(0,0,0,0.03);">
                            <td colspan="5" style="padding: 8px 12px; font-weight: 600;">Opening Balance</td>
                            <td style="text-align: right; padding: 8px 12px; font-weight: 600;">{{ number_format($openingBalance, 2) }}</td>
                        </tr>

                        @forelse($entries as $entry)
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 8px 12px;">{{ \Illuminate\Support\Carbon::parse($entry['date'])->format('d-m-Y') }}</td>
                                <td style="padding: 8px 12px;">{{ $entry['type'] }}</td>
                                <td style="padding: 8px 12px;">{{ $entry['reference'] }}</td>
                                <td style="text-align: right; padding: 8px 12px;">{{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '—' }}</td>
                                <td style="text-align: right; padding: 8px 12px;">{{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '—' }}</td>
                                <td style="text-align: right; padding: 8px 12px;">{{ number_format($entry['balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 16px; text-align: center; color: #6b7280;">
                                    No activity in this period.
                                </td>
                            </tr>
                        @endforelse

                        <tr style="background-color: rgba(0,0,0,0.03);">
                            <td colspan="5" style="padding: 8px 12px; font-weight: 700;">Closing Balance</td>
                            <td style="text-align: right; padding: 8px 12px; font-weight: 700;">{{ number_format($closingBalance, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-filament-panels::page>