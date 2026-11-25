<?php

use App\Mail\DailyOrdersReportMail;
use App\Models\Order;
use App\Models\Setting;
use App\Services\OrderExport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Models\PageView;
use App\Models\UserLog;
use App\Services\OrderCancellation;
use App\Services\OrderConfirmation;
use App\Services\VisitStats;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Orders whose confirmation link has run out get the status "expired": the sneakers go back in stock
// and the coupon use is released. Runs once a day at 03:00 (Belgrade time), see the schedule below.
Artisan::command('orders:expire', function (OrderCancellation $cancellation) {
    $expired = Order::with('items')
        ->where('status', Order::STATUS_UNCONFIRMED)
        ->where('created_at', '<', now()->subHours(OrderConfirmation::LINK_VALID_HOURS))
        ->get();

    $count = 0;

    foreach ($expired as $order) {
        $done = DB::transaction(function () use ($order, $cancellation) {
            // lockForUpdate + re-check: the customer may be confirming it right now.
            $locked = Order::lockForUpdate()->find($order->id);

            if (!$locked?->isUnconfirmed()) {
                return false;
            }

            $cancellation->cancel($order);
            $locked->update(['status' => Order::STATUS_EXPIRED]);

            return true;
        });

        if (!$done) {
            continue;
        }

        $count++;
        UserLog::record('order.expired', 'Order #' . $order->id . ' expired: not confirmed within ' . OrderConfirmation::LINK_VALID_HOURS . ' hours', $order);
    }

    $this->info($count . ' ' . Str::plural('order', $count) . ' marked as expired.');
})->purpose('Mark orders that were not confirmed in time as expired');

// Orders still not confirmed REMINDER_AFTER_HOURS (24h) after they were placed get one reminder mail with a new code.
// Only while the link still works (before LINK_VALID_HOURS), and only once per order (reminder_sent_at).
// Runs every hour, see the schedule below.
Artisan::command('orders:remind', function (OrderConfirmation $confirmation) {
    $orders = Order::where('status', Order::STATUS_UNCONFIRMED)
        ->whereNull('reminder_sent_at')
        ->where('created_at', '<=', now()->subHours(OrderConfirmation::REMINDER_AFTER_HOURS))
        ->where('created_at', '>', now()->subHours(OrderConfirmation::LINK_VALID_HOURS))
        ->get();

    $count = 0;

    foreach ($orders as $order) {
        // Claim the order first (lockForUpdate + re-check), so two runs at once can't both mail it,
        // and a customer confirming right now doesn't get a reminder.
        $claimed = DB::transaction(function () use ($order) {
            $locked = Order::lockForUpdate()->find($order->id);

            if (!$locked?->isUnconfirmed() || $locked->reminder_sent_at) {
                return false;
            }

            $locked->reminder_sent_at = now();
            $locked->save();

            return true;
        });

        if (!$claimed) {
            continue;
        }

        $order->refresh();

        if (!$confirmation->sendCode($order, reminder: true)) {
            // Not sent (mail server problem): free it, so the next hourly run tries again.
            $order->reminder_sent_at = null;
            $order->save();
            continue;
        }

        $count++;
        UserLog::record('order.reminder', 'Order ' . $order->number . ': confirmation reminder with a new code sent to ' . $order->email, $order);
    }

    $this->info($count . ' ' . Str::plural('reminder', $count) . ' sent.');
})->purpose('Mail a reminder (with a new code) for orders not confirmed within a day');

// At midnight: the orders placed yesterday (00:00-23:59) mailed to the order email from Admin > Settings,
// with an Excel file of all their details. Nothing is sent on a day without orders.
// --date=2026-11-23 sends the report for another day (e.g. after the server was down at midnight).
Artisan::command('orders:daily-report {--date= : day to report, Y-m-d (default: yesterday)}', function (OrderExport $export) {
    $day = $this->option('date') ? Carbon::createFromFormat('Y-m-d', $this->option('date'))->startOfDay() : today()->subDay();
    $orders = $export->orders($day, $day->copy()->addDay());

    if ($orders->isEmpty()) {
        $this->info('No orders on ' . $day->format('d.m.Y.') . ', nothing sent.');
        return;
    }

    $email = Setting::getValue(Setting::ORDER_EMAIL);
    if (!$email) {
        $this->error('No order email set in Admin > Settings.');
        return;
    }

    // Temporary file outside the public folder; deleted after sending, whatever happens.
    $path = tempnam(sys_get_temp_dir(), 'report');

    try {
        $export->writeXlsx($orders, $path);
        Mail::to($email)->send(new DailyOrdersReportMail($orders, $day, $path));
    } catch (\Throwable $e) {
        UserLog::recordFailure('orders.report_mail', 'Daily orders report for ' . $day->format('d.m.Y.') . ' could not be sent');
        report($e);
        $this->error('Sending failed: ' . $e->getMessage());
        return;
    } finally {
        @unlink($path);
    }

    UserLog::record('orders.report', 'Daily orders report for ' . $day->format('d.m.Y.') . ' (' . $orders->count() . ' orders) sent to ' . $email);
    $this->info('Report with ' . $orders->count() . ' ' . Str::plural('order', $orders->count()) . ' sent to ' . $email . '.');
})->purpose('Mail yesterday\'s orders as an Excel file to the shop');

// Counts the raw page_views into the daily totals (kept forever) used by admin > Analytics.
// Default: today and yesterday. After a long server downtime: php artisan visits:aggregate --days=60
Artisan::command('visits:aggregate {--days=2 : how many days back, today included}', function (VisitStats $stats) {
    $days = max(1, min((int) $this->option('days'), PageView::KEEP_DAYS + 1));

    for ($i = $days - 1; $i >= 0; $i--) {
        $stats->aggregate(today()->subDays($i));
    }

    $this->info('Visits counted for the last ' . $days . ' ' . Str::plural('day', $days) . '.');
})->purpose('Count page views into the daily visit statistics');

// The server's cron runs "php artisan schedule:run" every minute; Laravel then starts each task at its own time.
// Locally, "php artisan schedule:work" does the same while it is open.
Schedule::command('orders:expire')->dailyAt('03:00');
// withoutOverlapping: a slow mail server can't make two runs mail the same orders.
Schedule::command('orders:remind')->hourly()->withoutOverlapping();
Schedule::command('orders:daily-report')->dailyAt('00:00');
// Removes old password reset codes from password_reset_tokens (they stop working after 15 minutes anyway).
Schedule::command('auth:clear-resets')->hourly();
Schedule::command('visits:aggregate')->hourly();
// Deletes page_views rows older than PageView::KEEP_DAYS (60 days). Their daily totals stay.
Schedule::command('model:prune', ['--model' => [PageView::class]])->dailyAt('03:30');
// Deletes user logs older than UserLog::KEEP_MONTHS (3 months) for good. Once a week is enough.
Schedule::command('model:prune', ['--model' => [UserLog::class]])->weeklyOn(0, '04:00');
