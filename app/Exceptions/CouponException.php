<?php

namespace App\Exceptions;

use Exception;

// Thrown when a coupon code can't be used. The message is shown to the customer.
class CouponException extends Exception
{
}
