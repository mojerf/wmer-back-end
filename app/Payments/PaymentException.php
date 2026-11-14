<?php

namespace App\Payments;

use RuntimeException;

/** The gateway could not be reached or refused to register the payment. */
class PaymentException extends RuntimeException {}
