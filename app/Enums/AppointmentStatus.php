<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case InWaitingRoom = 'in_waiting_room';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    /**
     * Allowed transitions out of each status. Mirrors the matrix enforced in
     * AppointmentController::update — kept here as the canonical source.
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Scheduled => [self::InWaitingRoom, self::InProgress, self::Cancelled, self::NoShow],
            self::InWaitingRoom => [self::InProgress, self::Cancelled, self::NoShow, self::Scheduled],
            self::InProgress => [self::Completed, self::Cancelled, self::InWaitingRoom, self::Scheduled],
            self::NoShow => [self::InWaitingRoom, self::Scheduled],
            self::Cancelled => [self::Scheduled],
            self::Completed => [],
        };
    }

    public function canTransitionTo(self $other): bool
    {
        return in_array($other, $this->allowedNext(), true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
