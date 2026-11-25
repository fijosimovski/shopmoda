<?php

namespace App\Services;

use App\Mail\NewOrderConfirmationMail;
use App\Mail\NewOrderMail;
use App\Mail\OrderConfirmationCodeMail;
use App\Models\Order;
use App\Models\Setting;
use App\Models\UserLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Confirming an order with a code from the email:
 *   1. sendCode()  - right after checkout: mails a 6-digit code + a signed link to the confirm page.
 *   2. confirm()   - on that page: checks the code, marks the order as "new" and only then
 *                    sends the order confirmation to the customer and the order mail to the shop.
 */
class OrderConfirmation
{
    // How long the link in the email works (counted from when the order was placed).
    public const LINK_VALID_HOURS = 48;

    // Still not confirmed this many hours after ordering = a reminder with a new code is mailed (orders:remind).
    public const REMINDER_AFTER_HOURS = 24;

    // $reminder = true: the "you haven't confirmed yet" mail. A new code is made either way
    // (only the hash is stored, so the old one can't be resent); the old code stops working.
    // Returns false when the mail could not be sent.
    public function sendCode(Order $order, bool $reminder = false): bool
    {
        $code = (string) random_int(100000, 999999);

        // Only the hash is saved, like a password, so the code can't be read from the database.
        $order->confirmation_code = Hash::make($code);
        $order->save();

        try {
            Mail::to($order->email)->send(new OrderConfirmationCodeMail($order, $code, $this->link($order), $this->linkExpiresAt($order), $reminder));
        } catch (\Throwable $e) {
            // The order is already saved; a mail problem must not show an error to the customer.
            UserLog::recordFailure($reminder ? 'order.reminder_mail' : 'order.code_mail', 'Order confirmation ' . ($reminder ? 'reminder' : 'code') . ' mail sending failed', $order);
            report($e);

            return false;
        }

        return true;
    }

    // Signed link: it can't be changed to another order id, and it stops working LINK_VALID_HOURS after the order
    // was placed (also the link in the reminder, so it never outlives the orders:expire deadline).
    public function link(Order $order): string
    {
        return URL::temporarySignedRoute('orders.confirm', $this->linkExpiresAt($order), ['order' => $order->id]);
    }

    public function linkExpiresAt(Order $order): \Carbon\CarbonInterface
    {
        return ($order->created_at ?? now())->copy()->addHours(self::LINK_VALID_HOURS);
    }

    // Returns false when the code is wrong (or the order is not waiting for confirmation).
    public function confirm(Order $order, string $code): bool
    {
        // lockForUpdate: two clicks at the same moment can't both confirm it (and send the mails twice).
        $confirmed = DB::transaction(function () use ($order, $code) {
            $locked = Order::lockForUpdate()->find($order->id);

            if (!$locked->isUnconfirmed() || !$locked->confirmation_code || !Hash::check($code, $locked->confirmation_code)) {
                return false;
            }

            $locked->status = Order::STATUS_NEW;
            $locked->confirmed_at = now();
            $locked->confirmation_code = null; // the code can't be used twice
            $locked->save();

            return true;
        });

        if (!$confirmed) {
            return false;
        }

        $order->refresh();

        UserLog::record('order.confirmed', 'Order ' . $order->number . ' confirmed with the email code', $order);

        $order->load(['items.product.mainImage', 'discounts']);

        $this->sendOrderEmail($order);
        $this->sendOrderConfirmationEmail($order);

        return true;
    }

    // Sends the new order to the email set in the admin panel (Admin > Settings).
    private function sendOrderEmail(Order $order): void
    {
        $email = Setting::getValue(Setting::ORDER_EMAIL);

        if (!$email) {
            return;
        }

        try {
            Mail::to($email)->send(new NewOrderMail($order));
        } catch (\Throwable $e) {
            // The order is already confirmed; a mail problem must not show an error to the customer.
            UserLog::recordFailure('order.mail', 'Order mail sending failed', $order);
            report($e);
        }
    }

    // Sends the order confirmation to the customer.
    private function sendOrderConfirmationEmail(Order $order): void
    {
        if (!$order->email) {
            return;
        }

        try {
            Mail::to($order->email)->send(new NewOrderConfirmationMail($order));
        } catch (\Throwable $e) {
            UserLog::recordFailure('order.confirmation', 'Order confirmation mail sending failed', $order);
            report($e);
        }
    }
}
