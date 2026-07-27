<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * Use on a Filament CreateRecord page for any resource with an
 * auto-generated document number (invoice no, PO no, etc).
 *
 * Number generation happens at form-load time for a nice UX preview, but the
 * actual save can happen much later — so two users could theoretically be
 * handed the same "next" number. A unique DB constraint on the number column
 * is the real safety net (see migration 2026_07_27_130000); this trait makes
 * that collision invisible to the user by silently retrying with a fresh
 * number instead of surfacing a raw database error.
 */
trait RetriesOnDuplicateNumber
{
    /**
     * The data column that holds the generated number, e.g. 'sale_inv_no'.
     */
    abstract protected function numberColumn(): string;

    /**
     * Generate a fresh number. Typically just `SomeNumberService::generate()`.
     */
    abstract protected function generateNumber(): string;

    protected function handleRecordCreation(array $data): Model
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $model = static::getModel();

                return $model::create($data);
            } catch (QueryException $e) {
                $isLastAttempt = $attempt === $maxAttempts;

                if ($isLastAttempt || ! $this->isUniqueConstraintViolation($e)) {
                    throw $e;
                }

                // Collision on the number column — regenerate and try again.
                $data[$this->numberColumn()] = $this->generateNumber();
            }
        }

        // Unreachable, but keeps static analysis happy.
        throw new \RuntimeException('Failed to create record after retries.');
    }

    protected function isUniqueConstraintViolation(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'UNIQUE constraint failed') // SQLite
            || str_contains($message, '1062')                      // MySQL
            || str_contains($message, '23505');                    // PostgreSQL
    }
}