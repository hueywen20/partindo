<?php

namespace App\Filament\Admin\Pages\Reports\Concerns;

use Symfony\Component\HttpFoundation\StreamedResponse;

trait ExportsCsv
{
    /**
     * Stream an array of rows as a downloadable CSV.
     *
     * @param  array<int, string>  $headers  Column headings for the first row.
     * @param  iterable<int, array<int, mixed>>  $rows  Each row as a plain array of values, matching $headers order.
     */
    protected function streamCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}