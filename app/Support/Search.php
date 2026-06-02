<?php

namespace App\Support;

class Search
{
    /**
     * Escape user input for safe use inside a LIKE / ILIKE pattern.
     *
     * Eloquent escapes quotes but does not escape SQL wildcards (% and _),
     * which lets a malicious caller turn a substring filter into a
     * full-table scan (DoS) or bypass an intended exact match.
     */
    public static function escapeLike(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
