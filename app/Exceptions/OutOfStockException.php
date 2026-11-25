<?php

namespace App\Exceptions;

use Exception;

// Thrown when there isn't enough stock. Its message is safe to show to the user.
class OutOfStockException extends Exception
{
}
