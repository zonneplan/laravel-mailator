<?php

namespace Binarcode\LaravelMailator\Tests\Feature;

use Binarcode\LaravelMailator\Models\MailatorSchedule;
use Binarcode\LaravelMailator\Tests\Fixtures\InvoiceReminderMailable;
use Binarcode\LaravelMailator\Tests\Fixtures\SerializedConditionCondition;
use Binarcode\LaravelMailator\Tests\Fixtures\SingleSendingCondition;
use Binarcode\LaravelMailator\Tests\Fixtures\User;
use Binarcode\LaravelMailator\Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Spatie\TestTime\TestTime;

class SchedulerDefaultConstraintsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TestTime::freeze();
    }

    public function test_sending_email_only_once(): void
    {
        Mail::fake();
        Mail::assertNothingSent();

        MailatorSchedule::init('Invoice reminder.')
            ->recipients([
                'zoo@bar.com',
            ])
            ->mailable(
                (new InvoiceReminderMailable())->to('foo@bar.com')
            )
            ->days(1)
            ->constraint(new SingleSendingCondition)
            ->save();

        $_SERVER['can_send'] = true;
        MailatorSchedule::run();
        Mail::assertSent(InvoiceReminderMailable::class, 1);

        $_SERVER['can_send'] = false;

        MailatorSchedule::run();
        Mail::assertSent(InvoiceReminderMailable::class, 1);

        MailatorSchedule::run();
        Mail::assertSent(InvoiceReminderMailable::class, 1);

        Mail::assertSent(InvoiceReminderMailable::class, function (InvoiceReminderMailable $mail) {
            return $mail->hasTo('foo@bar.com') && $mail->hasTo('zoo@bar.com');
        });
    }

    public function test_sending_email_never_automatically(): void
    {
        Mail::fake();
        Mail::assertNothingSent();

        MailatorSchedule::init('Invoice reminder.')
            ->never()
            ->recipients([
                'zoo@bar.com',
            ])
            ->mailable(
                (new InvoiceReminderMailable())->to('foo@bar.com')
            )
            ->days(1)
            ->save();

        MailatorSchedule::run();
        Mail::assertSent(InvoiceReminderMailable::class, 0);
    }

    public function test_sending_email_manual_dont_send_automatically(): void
    {
        Mail::fake();
        Mail::assertNothingSent();

        MailatorSchedule::init('Invoice reminder.')
            ->manual()
            ->recipients([
                'zoo@bar.com',
            ])
            ->mailable(
                (new InvoiceReminderMailable())->to('foo@bar.com')
            )
            ->days(1)
            ->save();

        MailatorSchedule::run();
        Mail::assertSent(InvoiceReminderMailable::class, 0);
    }

    public function test_can_send_serialized_constrained(): void
    {
        Mail::fake();
        Mail::assertNothingSent();

        $scheduler = MailatorSchedule::init('Invoice reminder.')
            ->recipients([
                'zoo@bar.com',
            ])
            ->mailable(
                (new InvoiceReminderMailable())->to('foo@bar.com')
            )
            ->many()
            ->constraint(
                new SerializedConditionCondition(User::create([
                    'email' => 'john.doe@binarcode.com',
                ]))
            );

        $scheduler->save();

        MailatorSchedule::run();
        MailatorSchedule::run();

        Mail::assertSent(InvoiceReminderMailable::class, 2);
    }

    public function test_can_send_daily_before_target(): void
    {
        Mail::fake();
        Mail::assertNothingSent();

        $scheduler = MailatorSchedule::init('Invoice reminder.')
            ->recipients([
                'zoo@bar.com',
            ])
            ->mailable(
                (new InvoiceReminderMailable())->to('foo@bar.com')
            )
            ->daily()
            ->days(4)
            ->before(now()->addDays(7));

        $scheduler->save();

        MailatorSchedule::run();
        Mail::assertNothingSent();

        TestTime::addDays(4);
        MailatorSchedule::run();
        MailatorSchedule::run();

        Mail::assertSent(InvoiceReminderMailable::class, 1);

        TestTime::addDay();
        MailatorSchedule::run();
        MailatorSchedule::run();

        Mail::assertSent(InvoiceReminderMailable::class, 2);

        TestTime::addDay();
        MailatorSchedule::run();

        Mail::assertSent(InvoiceReminderMailable::class, 3);

        TestTime::addDay();
        MailatorSchedule::run();

        Mail::assertSent(InvoiceReminderMailable::class, 3);
    }

    public function test_can_send_weekly_before_target(): void
    {
        Mail::fake();
        Mail::assertNothingSent();

        $scheduler = MailatorSchedule::init('Invoice reminder.')
            ->recipients(['zoo@bar.com'])
            ->mailable(
                (new InvoiceReminderMailable())->to('foo@bar.com')
            )
            ->weekly()
            ->days(14)
            ->before(now()->addWeeks(3));

        $scheduler->save();

        MailatorSchedule::run();
        Mail::assertNothingSent();

        $this->travel(8)->days();
        MailatorSchedule::run();
        MailatorSchedule::run();

        Mail::assertSent(InvoiceReminderMailable::class, 1);

        // After 1 day it will not send it.
        $this->travel(1)->days();
        MailatorSchedule::run();
        Mail::assertSent(InvoiceReminderMailable::class, 1);

        $this->travel(6)->days();
        MailatorSchedule::run();
        MailatorSchedule::run();

        Mail::assertSent(InvoiceReminderMailable::class, 2);
    }

    public function test_can_send_daily_after_target(): void
    {
        Mail::fake();
        Mail::assertNothingSent();

        $scheduler = MailatorSchedule::init('Invoice reminder.')
            ->recipients([
                'zoo@bar.com',
            ])
            ->mailable(
                (new InvoiceReminderMailable())->to('foo@bar.com')
            )
            ->daily()
            ->days(2)
            ->after(now());

        $scheduler->save();

        MailatorSchedule::run();
        Mail::assertNothingSent();

        TestTime::addDay();
        MailatorSchedule::run();
        Mail::assertNothingSent();

        TestTime::addDays();
        MailatorSchedule::run();
        // Run this method twice in order to check whether the email is only sent once.
        MailatorSchedule::run();

        Mail::assertSent(InvoiceReminderMailable::class, 1);

        TestTime::addDay();
        MailatorSchedule::run();

        Mail::assertSent(InvoiceReminderMailable::class, 2);
    }

    public function test_can_send_hourly_after_target(): void
    {
        Mail::fake();
        Mail::assertNothingSent();

        $scheduler = MailatorSchedule::init('Invoice reminder.')
            ->recipients([
                'zoo@bar.com',
            ])
            ->mailable(
                (new InvoiceReminderMailable())->to('foo@bar.com')
            )
            ->hourly()
            ->hours(2)
            ->after(now());

        $scheduler->save();

        MailatorSchedule::run();
        Mail::assertNothingSent();

        TestTime::addHour();
        MailatorSchedule::run();
        Mail::assertNothingSent();

        TestTime::addHour();
        MailatorSchedule::run();
        // Run this method twice in order to check whether the email is only sent once.
        MailatorSchedule::run();

        Mail::assertSent(InvoiceReminderMailable::class, 1);

        TestTime::addHour();
        MailatorSchedule::run();

        Mail::assertSent(InvoiceReminderMailable::class, 2);
    }
}
