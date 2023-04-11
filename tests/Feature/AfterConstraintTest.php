<?php

namespace Binarcode\LaravelMailator\Tests\Feature;

use Binarcode\LaravelMailator\Constraints\AfterConstraint;
use Binarcode\LaravelMailator\Models\MailatorSchedule;
use Binarcode\LaravelMailator\Tests\Fixtures\InvoiceReminderMailable;
use Binarcode\LaravelMailator\Tests\TestCase;

class AfterConstraintTest extends TestCase
{
    public function test_past_target_with_after_constraint_day_bases(): void
    {
        $scheduler = $this->initScheduler()
            ->days(3)
            ->after(now()->subDay());

        $scheduler->save();

        $this->travel(1)->days();

        self::assertTrue(
            $scheduler->isFutureAction()
        );

        $this->travel(1)->days();

        $can = app(AfterConstraint::class)->canSend(
            $scheduler,
            $scheduler->logs
        );

        self::assertFalse(
            $scheduler->isFutureAction()
        );

        self::assertTrue(
            $this->canSend($scheduler)
        );
    }

    public function test_past_target_with_after_constraint_hourly_bases(): void
    {
        $scheduler = $this->initScheduler()
            ->hours(3)
            ->after(now()->subHours(1));

        $scheduler->save();

        $this->travel(1)->hours();

        // An hour before
        self::assertTrue(
            $scheduler->isFutureAction()
        );

        $this->travel(1)->hours();

        self::assertFalse(
            $scheduler->isFutureAction()
        );

        // Right now
        self::assertTrue(
            $this->canSend($scheduler)
        );

        $this->travel(1)->hours();

        self::assertFalse(
            $scheduler->isFutureAction()
        );

        // An hour later
        self::assertTrue(
            $this->canSend($scheduler)
        );
    }

    public function test_past_target_with_after_now_passed_after_constraint_hour_bases()
    {
        $scheduler = $this->initScheduler()
            ->hours(2);

        $scheduler->save();

        self::assertFalse(
            $this->canSend($scheduler)
        );

        $this->travel(3)->hours();

        self::assertTrue(
            $this->canSend($scheduler)
        );
    }

    public function test_past_target_with_after_now_before_after_constraint_minute_bases()
    {
        $scheduler = $this->initScheduler()
            ->minutes(1);
        $scheduler->save();

        self::assertTrue(
            $scheduler->isFutureAction()
        );

        self::assertFalse(
            $this->canSend($scheduler)
        );
    }

    public function test_past_target_with_after_now_passed_after_constraint_minute_bases()
    {
        $scheduler = $this->initScheduler()
            ->minutes(1);
        $scheduler->save();

        $this->travel(2)->minutes();

        self::assertFalse(
            $scheduler->isFutureAction()
        );

        self::assertTrue(
            $this->canSend($scheduler)
        );
    }

    public function test_past_target_with_after_now_equals_after_constraint_minute_bases()
    {
        $scheduler = $this->initScheduler()
            ->minutes(1);
        $this->travel(1)->minutes();

        $scheduler->save();

        self::assertFalse(
            $scheduler->isFutureAction()
        );

        self::assertTrue(
            $this->canSend($scheduler)
        );
    }

    private function canSend(MailatorSchedule $scheduler)
    {
        return  app(
            AfterConstraint::class
        )->canSend(
            $scheduler,
            $scheduler->logs
        );
    }

    private function initScheduler(): MailatorSchedule
    {
        return MailatorSchedule::init('Invoice reminder.')
            ->recipients($mail = 'zoo@bar.com')
            ->mailable(
                (new InvoiceReminderMailable())->to('foo@bar.com')
            )
            ->after(now());
    }
}
