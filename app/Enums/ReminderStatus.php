<?php

namespace App\Enums;

enum ReminderStatus: string
{
    case Pending = 'pending';
    case Contacted = 'contacted';
    case Dismissed = 'dismissed';
    case Fulfilled = 'fulfilled';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
