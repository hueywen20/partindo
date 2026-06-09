<?php

namespace App\Console\Commands;

use App\Models\Quotation;
use Illuminate\Console\Command;

/**
 * Marks quotations as 'expired' when their valid_until date has passed.
 *
 * Schedule in routes/console.php (Laravel 11+):
 *
 *   Schedule::command('quotations:expire')->daily();
 */
class ExpireQuotations extends Command
{
    protected $signature   = 'quotations:expire';
    protected $description = 'Mark overdue quotations as expired';

    public function handle(): int
    {
        $count = Quotation::whereNotIn('status', ['accepted', 'expired'])
            ->whereDate('valid_until', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} quotation(s).");

        return self::SUCCESS;
    }
}