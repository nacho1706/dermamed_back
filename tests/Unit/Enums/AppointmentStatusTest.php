<?php

namespace Tests\Unit\Enums;

use App\Enums\AppointmentStatus;
use PHPUnit\Framework\TestCase;

class AppointmentStatusTest extends TestCase
{
    public function test_values_lists_the_canonical_status_strings(): void
    {
        $this->assertSame(
            ['scheduled', 'in_waiting_room', 'in_progress', 'completed', 'cancelled', 'no_show'],
            AppointmentStatus::values(),
        );
    }

    public function test_completed_is_terminal(): void
    {
        $this->assertEmpty(AppointmentStatus::Completed->allowedNext());
        $this->assertFalse(AppointmentStatus::Completed->canTransitionTo(AppointmentStatus::InProgress));
    }

    public function test_scheduled_allows_intake_paths(): void
    {
        $next = AppointmentStatus::Scheduled->allowedNext();
        $this->assertContains(AppointmentStatus::InWaitingRoom, $next);
        $this->assertContains(AppointmentStatus::InProgress, $next);
        $this->assertContains(AppointmentStatus::Cancelled, $next);
        $this->assertContains(AppointmentStatus::NoShow, $next);
    }

    public function test_can_transition_to_matches_allowed_next(): void
    {
        $this->assertTrue(AppointmentStatus::InWaitingRoom->canTransitionTo(AppointmentStatus::InProgress));
        $this->assertFalse(AppointmentStatus::InWaitingRoom->canTransitionTo(AppointmentStatus::Completed));
    }
}
