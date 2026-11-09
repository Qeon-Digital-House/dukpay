<?php

namespace Rrq\Dukpay\Exceptions;

use Throwable;

/** Transport-level failure: connection error, non-2xx status, or unparsable body. */
class DukPayHttpException extends DukPayException
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly ?string $body = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status ?? 0, $previous);
    }
}
