<?php

namespace Tests\Feature;

use App\Models\ClinicBranch;
use App\Support\BranchSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_schedule_preserves_legacy_unrestricted_booking(): void
    {
        $branch = new ClinicBranch(['booking_schedule_enabled' => false]);

        $this->assertSame(['available' => true, 'from' => null, 'to' => null], BranchSchedule::forDate($branch, '2026-10-25'));
    }

    public function test_weekly_schedule_resolves_sunday_and_closed_weekdays(): void
    {
        $branch = $this->branchWithSchedule([
            '0' => ['open' => true, 'from' => '09:30', 'to' => '16:00'],
        ]);

        $this->assertSame(['available' => true, 'from' => '09:30', 'to' => '16:00'], BranchSchedule::forDate($branch, '2026-10-25'));
        $this->assertSame(['available' => false, 'from' => null, 'to' => null], BranchSchedule::forDate($branch, '2026-10-26'));
    }

    public function test_date_exception_overrides_weekly_hours_and_can_close_or_open_a_day(): void
    {
        $branch = $this->branchWithSchedule(
            ['0' => ['open' => true, 'from' => '09:00', 'to' => '17:00']],
            [
                ['date' => '2026-10-25', 'closed' => true, 'from' => null, 'to' => null],
                ['date' => '2026-10-26', 'closed' => false, 'from' => '11:00', 'to' => '13:30'],
            ],
        );

        $this->assertSame(['available' => false, 'from' => null, 'to' => null], BranchSchedule::forDate($branch, '2026-10-25'));
        $this->assertSame(['available' => true, 'from' => '11:00', 'to' => '13:30'], BranchSchedule::forDate($branch, '2026-10-26'));
    }

    public function test_malformed_or_non_increasing_time_ranges_fail_closed(): void
    {
        $branch = $this->branchWithSchedule([
            '0' => ['open' => true, 'from' => '17:00', 'to' => '09:00'],
        ]);

        $this->assertSame(['available' => false, 'from' => null, 'to' => null], BranchSchedule::forDate($branch, '2026-10-25'));

        $branch->weekly_schedule = ['0' => ['open' => true, 'from' => '9am', 'to' => '17:00']];
        $this->assertSame(['available' => false, 'from' => null, 'to' => null], BranchSchedule::forDate($branch, '2026-10-25'));

        $branch->schedule_exceptions = [['date' => '2026-10-25', 'from' => '09:00', 'to' => '17:00']];
        $this->assertSame(['available' => false, 'from' => null, 'to' => null], BranchSchedule::forDate($branch, '2026-10-25'));
        $this->assertSame(['available' => false, 'from' => null, 'to' => null], BranchSchedule::forDate($branch, '2026-02-30'));
    }

    public function test_midnight_is_allowed_only_as_the_end_of_a_same_day_schedule(): void
    {
        $branch = $this->branchWithSchedule([
            '0' => ['open' => true, 'from' => '16:00', 'to' => '00:00'],
        ], [
            ['date' => '2026-10-26', 'closed' => false, 'from' => '18:30', 'to' => '00:00'],
        ]);

        $this->assertSame(['available' => true, 'from' => '16:00', 'to' => '00:00'], BranchSchedule::forDate($branch, '2026-10-25'));
        $this->assertSame(['available' => true, 'from' => '18:30', 'to' => '00:00'], BranchSchedule::forDate($branch, '2026-10-26'));

        $branch->weekly_schedule = ['0' => ['open' => true, 'from' => '00:00', 'to' => '00:00']];
        $this->assertSame(['available' => false, 'from' => null, 'to' => null], BranchSchedule::forDate($branch, '2026-10-25'));

        $branch->weekly_schedule = ['0' => ['open' => true, 'from' => '22:00', 'to' => '21:00']];
        $this->assertSame(['available' => false, 'from' => null, 'to' => null], BranchSchedule::forDate($branch, '2026-10-25'));
    }

    private function branchWithSchedule(array $weekly, array $exceptions = []): ClinicBranch
    {
        return new ClinicBranch([
            'booking_schedule_enabled' => true,
            'weekly_schedule' => $weekly,
            'schedule_exceptions' => $exceptions,
        ]);
    }
}
