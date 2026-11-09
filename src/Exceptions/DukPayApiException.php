<?php

namespace Rrq\Dukpay\Exceptions;

use Rrq\Dukpay\DukPayResponse;

/** DukPay answered with a non-zero business code. */
class DukPayApiException extends DukPayException
{
    public function __construct(public readonly DukPayResponse $response)
    {
        parent::__construct(
            sprintf('DukPay [%s] error %d: %s', $response->api, $response->code, $response->message ?? 'unknown error'),
            $response->code,
        );
    }

    public function getResponse(): DukPayResponse
    {
        return $this->response;
    }
}
