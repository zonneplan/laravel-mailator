<?php

namespace Binarcode\LaravelMailator\Constraints;

use Binarcode\LaravelMailator\Models\MailatorSchedule;
use Illuminate\Support\Collection;

class AfterConstraint implements SendScheduleConstraint
{
    public function canSend(MailatorSchedule $schedule, Collection $logs): bool
    {
        if (! $schedule->isAfter()) {
            return true;
        }

        if (is_null($schedule->timestamp_target)) {
            return true;
        }

        if ($schedule->toDays() > 0) {
            if (now()->floorSeconds()->lt($schedule->timestampTarget()->addDays($schedule->toDays()))) {
                return false;
            }

            return $schedule->timestamp_target->diffInDays(now()->floorSeconds()) >= $schedule->toDays();
        }

        if ($schedule->toHours() > 0) {
            if (now()->floorSeconds()->lt($schedule->timestampTarget()->addHours($schedule->toHours()))) {
                return false;
            }

            return $schedule->timestamp_target->diffInHours(now()->floorSeconds()) >= $schedule->toHours();
        }

        if (now()->floorSeconds()->lt($schedule->timestampTarget()->addMinutes($schedule->delay_minutes))) {
            return false;
        }

        return $schedule->timestamp_target->diffInMinutes(now()->floorSeconds()) >= $schedule->delay_minutes;
    }
}
