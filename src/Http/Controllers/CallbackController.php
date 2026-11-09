<?php

namespace Rrq\Dukpay\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Rrq\Dukpay\DukPay;
use Rrq\Dukpay\Events\DukPayCallbackReceived;
use Rrq\Dukpay\Exceptions\DukPayDecryptionException;
use Rrq\Dukpay\Exceptions\InvalidSignatureException;

class CallbackController extends Controller
{
    public function __invoke(Request $request, DukPay $dukpay): JsonResponse
    {
        try {
            $callback = $dukpay->parseCallback($request);
        } catch (InvalidSignatureException|DukPayDecryptionException $e) {
            Log::warning('DukPay callback rejected', [
                'reason' => $e->getMessage(),
                'requestKey' => $request->header('X-DUKPAY-REQ-KEY'),
                'ip' => $request->ip(),
            ]);

            return new JsonResponse(['code' => 1001, 'message' => 'signature error'], 401);
        }

        // Listener exceptions bubble up -> HTTP 500 -> DukPay retries the notification.
        event(new DukPayCallbackReceived($callback));

        return new JsonResponse(['code' => 0]);
    }
}
