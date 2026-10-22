<?php

namespace Parth\LaravelRbacSuite\Traits;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

trait ResolvesEncryptedId
{
    /**
     * Resolve string, numeric, or encrypted ID safely to integer.
     */
    protected function resolveEncryptedId(string|int $id, string $entityName = 'record'): int
    {
        if (is_numeric($id)) {
            return (int) $id;
        }

        try {
            return (int) Crypt::decrypt($id);
        } catch (DecryptException) {
            abort(404, "Invalid {$entityName} identifier.");
        }
    }
}
