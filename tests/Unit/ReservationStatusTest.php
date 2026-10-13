<?php

namespace Tests\Unit;

use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReservationStatusTest extends TestCase
{
    private function reservation(string $status, string $start, string $end): Reservation
    {
        return new Reservation([
            'status' => $status,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    public function test_past_approved_reservation_is_finished(): void
    {
        Carbon::setTestNow('2026-10-13 12:00:00');

        $res = $this->reservation('approved', '2026-10-07 07:30', '2026-10-07 20:00');

        $this->assertTrue($res->isFinished());
        $this->assertFalse($res->isOngoing());
        $this->assertSame('finished', $res->displayStatus());
        $this->assertSame('Selesai', $res->displayStatusLabel());
    }

    public function test_currently_running_approved_reservation_is_ongoing(): void
    {
        Carbon::setTestNow('2026-10-13 12:00:00');

        $res = $this->reservation('approved', '2026-10-13 08:00', '2026-10-13 17:00');

        $this->assertTrue($res->isOngoing());
        $this->assertFalse($res->isFinished());
        $this->assertSame('ongoing', $res->displayStatus());
        $this->assertSame('Berlangsung', $res->displayStatusLabel());
    }

    public function test_future_approved_reservation_keeps_base_status(): void
    {
        Carbon::setTestNow('2026-10-13 12:00:00');

        $res = $this->reservation('approved', '2026-10-14 08:00', '2026-10-14 10:00');

        $this->assertFalse($res->isFinished());
        $this->assertFalse($res->isOngoing());
        $this->assertSame('approved', $res->displayStatus());
        $this->assertSame('Approved', $res->displayStatusLabel());
    }

    public function test_past_pending_reservation_is_not_treated_as_finished(): void
    {
        Carbon::setTestNow('2026-10-13 12:00:00');

        $res = $this->reservation('pending', '2026-10-07 08:00', '2026-10-07 10:00');

        $this->assertFalse($res->isFinished());
        $this->assertFalse($res->isOngoing());
        $this->assertSame('pending', $res->displayStatus());
    }

    public function test_past_cancelled_reservation_is_not_treated_as_finished(): void
    {
        Carbon::setTestNow('2026-10-13 12:00:00');

        $res = $this->reservation('cancelled', '2026-10-07 08:00', '2026-10-07 10:00');

        $this->assertFalse($res->isFinished());
        $this->assertSame('cancelled', $res->displayStatus());
    }

    public function test_has_started_reflects_the_start_time(): void
    {
        Carbon::setTestNow('2026-10-13 12:00:00');

        $this->assertTrue($this->reservation('approved', '2026-10-13 08:00', '2026-10-13 17:00')->hasStarted());
        $this->assertFalse($this->reservation('approved', '2026-10-13 13:00', '2026-10-13 15:00')->hasStarted());
    }
}
